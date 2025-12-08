<?php

namespace App\Http\Controllers;

use App\Models\ConsultaIA;
use App\Models\Ejercicio;
use App\Models\Tema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Subtema;

class ConsultaIAController extends Controller
{
    // ==========================================
    // 1. GENERAR CUESTIONARIO (Quiz)
    // ==========================================
    public function generarCuestionario(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tema_id' => 'required|integer',
                'subtema_id' => 'nullable|integer',
                'cantidad' => 'integer|min:3|max:10|default:5',
                'dificultad' => 'required|string|in:facil,intermedio,dificil'
            ]);

            $usuario = $request->user();
            $contexto = $this->obtenerContextoTema($validated['tema_id']);
            
            $prompt = "Contexto educativo:\n{$contexto}\n\n";
            $prompt .= "Genera un cuestionario de {$validated['cantidad']} preguntas de selección múltiple.\n";
            $prompt .= "Dificultad: {$validated['dificultad']}.\n";
            $prompt .= "Formato requerido: Un único ARRAY JSON válido. Ejemplo:\n";
            $prompt .= "[\n  {\n    \"id\": 1,\n    \"pregunta\": \"Texto...\",\n    \"opciones\": [\"a) Op1\", \"b) Op2\", \"c) Op3\", \"d) Op4\"],\n    \"respuesta_correcta\": \"a\",\n    \"explicacion\": \"...\"\n  }\n]\n\n";
            $prompt .= "Responde SOLO con el JSON array.";

            $resultadoIA = $this->llamarGemini($prompt);

            if ($resultadoIA['success']) {
                $quizLimpio = $this->limpiarJSON($resultadoIA['data']);
                $quizData = json_decode($quizLimpio, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $consulta = ConsultaIA::create([
                        'usuario_id' => $usuario->id,
                        'pregunta' => "Quiz generado: {$validated['cantidad']} preguntas ({$validated['dificultad']})",
                        'respuesta_ia' => $quizLimpio,
                        'tipo' => 'quiz_generado',
                    ]);

                    return response()->json([
                        'message' => 'Cuestionario generado',
                        'quiz_id' => $consulta->id,
                        'preguntas' => $quizData
                    ], 201);
                }
            }
            return response()->json(['error' => 'Error formato IA', 'raw' => $resultadoIA['data']], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. RESPONDER CUESTIONARIO
    // ==========================================
    public function responderCuestionario(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quiz_id' => 'required|integer',
                'respuestas' => 'required|array',
            ]);

            $usuario = $request->user();
            $quizOriginal = ConsultaIA::where('id', $validated['quiz_id'])
                ->where('usuario_id', $usuario->id)
                ->firstOrFail();

            $preguntasQuiz = json_decode($quizOriginal->respuesta_ia, true);
            
            $puntaje = 0;
            $total = count($preguntasQuiz);
            $detalles = [];
            $erroresParaIA = [];

            foreach ($preguntasQuiz as $index => $pregunta) {
                $respuestaUsuario = null;
                foreach ($validated['respuestas'] as $res) {
                    $preguntaId = $pregunta['id'] ?? ($index + 1);
                    if (isset($res['id']) && $res['id'] == $preguntaId) {
                        $respuestaUsuario = $res['seleccion'];
                        break;
                    }
                }

                $letraCorrecta = strtolower(substr($pregunta['respuesta_correcta'], 0, 1));
                $letraUsuario = strtolower(substr($respuestaUsuario ?? '', 0, 1));
                $esCorrecto = ($letraCorrecta === $letraUsuario);
                
                if ($esCorrecto) $puntaje++;
                else {
                    $erroresParaIA[] = [
                        'pregunta' => $pregunta['pregunta'],
                        'correcta' => $pregunta['respuesta_correcta'],
                        'tu_respuesta' => $respuestaUsuario ?? 'Sin responder'
                    ];
                }

                $detalles[] = [
                    'pregunta_id' => $pregunta['id'] ?? ($index + 1),
                    'es_correcto' => $esCorrecto,
                    'correcta' => $pregunta['respuesta_correcta'],
                    'explicacion' => $pregunta['explicacion'] ?? ''
                ];
            }

            $calificacion = ($total > 0) ? round(($puntaje / $total) * 100) : 0;

            $feedbackGeneral = "¡Excelente trabajo!";
            if (count($erroresParaIA) > 0) {
                $promptFeedback = "Estudiante obtuvo {$calificacion}/100.\nErrores:\n" . json_encode($erroresParaIA) . "\n\nDame feedback constructivo breve sin dar respuestas directas.";
                $resIA = $this->llamarGemini($promptFeedback);
                if ($resIA['success']) $feedbackGeneral = $resIA['data'];
            }

            ConsultaIA::create([
                'usuario_id' => $usuario->id,
                'pregunta' => "Intento Quiz #{$validated['quiz_id']}",
                'respuesta_ia' => json_encode($detalles),
                'retroalimentacion' => $feedbackGeneral,
                'tipo' => 'intento_quiz',
                'es_correcto' => ($calificacion >= 60)
            ]);

            return response()->json([
                'calificacion' => (int)$calificacion,
                'puntaje_texto' => "{$puntaje}/{$total}",
                'retroalimentacion' => $feedbackGeneral,
                'es_correcto' => ($calificacion >= 60),
                'detalles' => $detalles
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. VERIFICAR UN SOLO EJERCICIO (La función que faltaba)
    // ==========================================
    public function verificarEjercicio(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ejercicio_id' => 'required|integer',
                'respuesta_estudiante' => 'required|string',
            ]);

            $usuario = $request->user();
            $ejercicio = Ejercicio::findOrFail($validated['ejercicio_id']);

            // Obtenemos contexto
            $contexto = $this->obtenerContextoEjercicio($ejercicio);

            // Llamada a la IA (usando la función auxiliar segura)
            $resultado = $this->verificarRespuestaConGemini(
                $ejercicio,
                $validated['respuesta_estudiante'],
                $contexto
            );

            // Guardar historial
            $consulta = ConsultaIA::create([
                'usuario_id' => $usuario->id,
                'ejercicio_id' => $ejercicio->id,
                'pregunta' => $validated['respuesta_estudiante'],
                'respuesta_ia' => $resultado['evaluacion'],
                'retroalimentacion' => $resultado['retroalimentacion'],
                'tipo' => 'respuesta_ejercicio',
                'es_correcto' => $resultado['es_correcto'],
            ]);

            return response()->json([
                'message' => 'Respuesta evaluada',
                'es_correcto' => $resultado['es_correcto'],
                'evaluacion' => $resultado['evaluacion'],
                'retroalimentacion' => $resultado['retroalimentacion'],
                'consulta' => $consulta,
                // Agregamos calificacion simulada para evitar crash en Flutter si lo espera
                'calificacion' => $resultado['es_correcto'] ? 100 : 0 
            ], 200);

        } catch (\Exception $e) {
            Log::error('ERROR VERIFICAR EJERCICIO: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 4. FUNCIONES AUXILIARES PRIVADAS
    // ==========================================

    private function llamarGemini($prompt): array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            Log::error('GEMINI ERROR: Falta API KEY');
            return ['success' => false, 'data' => 'Error config server'];
        }

        // Usamos modelo estable 1.5
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

        try {
            $response = Http::withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . '?key=' . $apiKey, [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);

            if ($response->successful()) {
                $jsonResponse = $response->json();
                if (isset($jsonResponse['candidates'][0]['content']['parts'][0]['text'])) {
                    return ['success' => true, 'data' => $jsonResponse['candidates'][0]['content']['parts'][0]['text']];
                } else {
                    return ['success' => false, 'data' => 'IA no respondió texto.'];
                }
            } else {
                Log::error('GEMINI API ERROR: ' . $response->body());
                return ['success' => false, 'data' => 'Error Google: ' . $response->status()];
            }

        } catch (\Exception $e) {
            Log::error('GEMINI EXCEPTION: ' . $e->getMessage());
            return ['success' => false, 'data' => 'Error conexión'];
        }
    }

    private function limpiarJSON($texto) {
        $texto = preg_replace('/```json\s*/', '', $texto);
        $texto = preg_replace('/```\s*$/', '', $texto);
        return trim($texto);
    }

    private function obtenerContextoTema($temaId) {
        $tema = Tema::with('subtemas.contenidos')->find($temaId);
        if (!$tema) return "Tema general.";
        $ctx = "Tema: {$tema->titulo}. Descripción: {$tema->descripcion}. ";
        foreach($tema->subtemas as $sub) {
            $ctx .= "Subtema: {$sub->titulo}. ";
            foreach($sub->contenidos as $cont) $ctx .= "Info: " . substr($cont->cuerpo, 0, 100) . "... ";
        }
        return $ctx;
    }
    /**
     * Obtener el contexto educativo completo de un Subtema
     */
    private function obtenerContextoSubtema($subtemaId)
    {
        // 1. Buscamos el subtema cargando también sus contenidos
        // Asegúrate de que tu modelo Subtema tenga la relación public function contenidos()
        $subtema = \App\Models\Subtema::with('contenidos')->find($subtemaId);

        // 2. Si no existe, devolvemos un texto genérico para que no falle
        if (!$subtema) {
            return "Contexto general del curso.";
        }

        // 3. Empezamos a construir el texto para la IA
        $ctx = "Título del Subtema: {$subtema->titulo}.\n";
        $ctx .= "Descripción: {$subtema->descripcion}.\n";

        // 4. Agregamos la información teórica directa (si existe en la tabla subtemas)
        if (!empty($subtema->informacion)) {
            $ctx .= "Teoría base: {$subtema->informacion}\n";
        }

        // 5. Recorremos los contenidos extra (videos, textos largos) y los agregamos
        if ($subtema->contenidos && $subtema->contenidos->count() > 0) {
            foreach ($subtema->contenidos as $cont) {
                // Tomamos solo los primeros 500 caracteres de cada contenido 
                // para no exceder el límite de tokens de la IA y ahorrar costos.
                $extracto = substr($cont->cuerpo, 0, 500);
                $ctx .= "Información adicional ({$cont->titulo}): {$extracto}...\n";
            }
        }

        return $ctx;
    }

    private function obtenerContextoEjercicio($ejercicio) {
        $subtema = \App\Models\Subtema::with('contenidos')->find($subtemaId);
        $contexto = "Ejercicio: {$ejercicio->titulo}. Pregunta: {$ejercicio->pregunta}. Solución: {$ejercicio->solucion}. ";
        if ($subtema) {
            $contexto .= "Tema relacionado: {$subtema->titulo}. ";
            $contenido = $subtema->contenidos->first();
            if ($contenido) $contexto .= "Teoría: " . substr($contenido->cuerpo, 0, 200);
        }
        return $contexto;
    }

    private function verificarRespuestaConGemini($ejercicio, $respuestaEstudiante, $contexto): array
    {
        $prompt = "Contexto: {$contexto}\n";
        $prompt .= "Pregunta: \"{$ejercicio->pregunta}\"\n";
        $prompt .= "Solución: \"{$ejercicio->solucion}\"\n";
        $prompt .= "Respuesta Estudiante: \"{$respuestaEstudiante}\"\n";
        $prompt .= "Devuelve JSON: { \"es_correcto\": boolean, \"evaluacion\": string, \"retroalimentacion\": string }";

        $resultadoIA = $this->llamarGemini($prompt);

        if ($resultadoIA['success']) {
            $textoLimpio = $this->limpiarJSON($resultadoIA['data']);
            $json = json_decode($textoLimpio, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'es_correcto' => $json['es_correcto'] ?? false,
                    'evaluacion' => $json['evaluacion'] ?? 'Evaluación no disponible',
                    'retroalimentacion' => $json['retroalimentacion'] ?? 'Sin comentarios',
                ];
            }
        }
        return [
            'es_correcto' => false,
            'evaluacion' => 'Error al conectar con IA',
            'retroalimentacion' => 'Intenta de nuevo.',
        ];
    }
}