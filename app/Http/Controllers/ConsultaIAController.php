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
            // 1. CORRECCIÓN: Quitamos "|default:5" porque esa regla no existe en Laravel
            $validated = $request->validate([
                'tema_id' => 'nullable|integer',
                'subtema_id' => 'nullable|integer',
                'cantidad' => 'integer|min:3|max:10', // <--- SIN DEFAULT AQUÍ
                'dificultad' => 'required|string|in:facil,intermedio,dificil'
            ]);

            // 2. Asignamos el valor por defecto aquí (PHP puro)
            // Si el frontend no envió 'cantidad', usamos 5.
            $cantidadPreguntas = $request->input('cantidad', 5);

            $usuario = $request->user();
            $tituloContexto = "";
            $contexto = "";

            // 3. Decidimos qué contexto usar
            if (!empty($validated['subtema_id'])) {
                $contexto = $this->obtenerContextoSubtema($validated['subtema_id']);
                $tituloContexto = "Quiz Subtema #{$validated['subtema_id']}";
            } else {
                $contexto = $this->obtenerContextoTema($validated['tema_id']);
                $tituloContexto = "Quiz Tema #{$validated['tema_id']}";
            }
            
            // 4. Usamos la variable $cantidadPreguntas en el prompt
            $prompt = "Contexto educativo:\n{$contexto}\n\n";
            $prompt .= "Genera un cuestionario de {$cantidadPreguntas} preguntas de selección múltiple.\n";
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
                        'pregunta' => "{$tituloContexto} ({$validated['dificultad']})",
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

    /**
     * Llamar a la API de Gemini (Versión Compatible gemini-pro)
     */
    private function llamarGemini($prompt): array
    {
        // 1. LIMPIEZA DE LLAVE: trim() borra espacios invisibles que causan error 400
        $apiKey = trim(env('GEMINI_API_KEY')); 
        
        if (empty($apiKey)) {
            Log::error('GEMINI ERROR: No se encontró la API KEY');
            return ['success' => false, 'data' => 'Error: Falta API Key en .env'];
        }

        // 2. CAMBIO DE MODELO: Usamos 'gemini-pro' que es el más estándar y gratuito
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

        $data = [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ];

        try {
            // 3. PETICIÓN HTTP
            $response = Http::withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . '?key=' . $apiKey, $data);

            // 4. MANEJO DE ÉXITO
            if ($response->successful()) {
                $jsonResponse = $response->json();
                
                if (isset($jsonResponse['candidates'][0]['content']['parts'][0]['text'])) {
                    return [
                        'success' => true, 
                        'data' => $jsonResponse['candidates'][0]['content']['parts'][0]['text']
                    ];
                } else {
                    return ['success' => false, 'data' => 'IA respondió vacío.']; 
                }
            } else { 
                // 5. MANEJO DE ERROR DETALLADO
                // Aquí capturamos el mensaje real de Google (ej: "API Key not valid")
                $errorBody = $response->body();
                Log::error('GEMINI API ERROR: ' . $errorBody);
                
                return [
                    'success' => false, 
                    // Concatenamos el cuerpo del error para verlo en Flutter
                    'data' => 'Google Error (' . $response->status() . '): ' . $errorBody
                ];
            }

        } catch (\Exception $e) {
            Log::error('GEMINI EXCEPTION: ' . $e->getMessage());
            return [
                'success' => false, 
                'data' => 'Error conexión: ' . $e->getMessage()
            ];
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
    /**
     * 4. EVALUAR QUIZ COMPLETO (Batch)
     * Recibe todas las respuestas de los ejercicios estáticos y las evalúa juntas.
     */
    public function evaluarQuiz(Request $request): JsonResponse
    {
        try {
            // Validamos que llegue un array de respuestas
            $validated = $request->validate([
                'respuestas' => 'required|array', 
                // Estructura esperada: [{ "ejercicio_id": 1, "respuesta": "texto" }, ...]
                'respuestas.*.ejercicio_id' => 'required|integer',
                'respuestas.*.respuesta' => 'required|string',
            ]);

            // 1. Obtener todos los ejercicios reales de la BD para tener las preguntas y soluciones
            $ids = array_column($validated['respuestas'], 'ejercicio_id');
            $ejerciciosDb = Ejercicio::whereIn('id', $ids)->get()->keyBy('id');

            // 2. Construir el Prompt Masivo para la IA
            $prompt = "Actúa como un profesor. Evalúa las siguientes respuestas de un estudiante a un examen:\n\n";

            foreach ($validated['respuestas'] as $item) {
                $ejercicio = $ejerciciosDb[$item['ejercicio_id']] ?? null;
                if (!$ejercicio) continue;

                $prompt .= "--- EJERCICIO ID {$item['ejercicio_id']} ---\n";
                $prompt .= "PREGUNTA: {$ejercicio->pregunta}\n";
                $prompt .= "SOLUCIÓN CORRECTA: {$ejercicio->solucion}\n";
                $prompt .= "RESPUESTA DEL ALUMNO: \"{$item['respuesta']}\"\n";
                $prompt .= "---------------------------------------------------\n";
            }

            $prompt .= "\nInstrucciones de Salida:\n";
            $prompt .= "1. Calcula una calificación final del 0 al 100 basada en los aciertos.\n";
            $prompt .= "2. Devuelve OBLIGATORIAMENTE un único JSON con este formato:\n";
            $prompt .= "{\n";
            $prompt .= "  \"nota_global\": 85,\n"; // Entero
            $prompt .= "  \"comentario_general\": \"Buen trabajo, pero repasa...\",\n";
            $prompt .= "  \"detalles\": [\n";
            $prompt .= "    { \"ejercicio_id\": 1, \"es_correcto\": true, \"feedback\": \"Bien explicado\" },\n";
            $prompt .= "    { \"ejercicio_id\": 5, \"es_correcto\": false, \"feedback\": \"Te faltó...\" }\n";
            $prompt .= "  ]\n";
            $prompt .= "}";
            $prompt .= "\nResponde SOLO con el JSON.";

            // 3. Llamar a Gemini
            $resultadoIA = $this->llamarGemini($prompt);

            if ($resultadoIA['success']) {
                $textoLimpio = $this->limpiarJSON($resultadoIA['data']);
                $json = json_decode($textoLimpio, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    
                    // Aquí podrías guardar el intento en la BD si quisieras (opcional)

                    return response()->json([
                        'message' => 'Quiz evaluado correctamente',
                        'resultado' => $json // Enviamos el JSON de la IA tal cual
                    ], 200);
                }
            }

            return response()->json(['error' => 'La IA no devolvió un formato válido', 'raw' => $resultadoIA['data']], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}