<?php

namespace App\Http\Controllers;

use App\Models\ConsultaIA;
use App\Models\Ejercicio;
use App\Models\Tema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConsultaIAController extends Controller
{
    // ==========================================
    // 1. GENERAR CUESTIONARIO (Quiz de varias preguntas)
    // ==========================================
    public function generarCuestionario(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tema_id' => 'required|integer',
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
    // 2. RESPONDER CUESTIONARIO (Calcula Nota + Feedback)
    // ==========================================
    public function responderCuestionario(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quiz_id' => 'required|integer',
                'respuestas' => 'required|array', // [{'id': 1, 'seleccion': 'a'}, ...]
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
                // Buscar respuesta del usuario
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

            // Calculamos nota (Aseguramos que sea un número, nunca null)
            $calificacion = ($total > 0) ? round(($puntaje / $total) * 100) : 0;

            // Feedback IA solo si hay errores
            $feedbackGeneral = "¡Excelente trabajo! Has dominado el tema.";
            if (count($erroresParaIA) > 0) {
                $promptFeedback = "Estudiante obtuvo {$calificacion}/100.\nErrores:\n" . json_encode($erroresParaIA) . "\n\nDame feedback constructivo breve sin dar respuestas directas.";
                $resIA = $this->llamarGemini($promptFeedback);
                if ($resIA['success']) $feedbackGeneral = $resIA['data'];
            }

            // Guardar intento
            ConsultaIA::create([
                'usuario_id' => $usuario->id,
                'pregunta' => "Intento Quiz #{$validated['quiz_id']}",
                'respuesta_ia' => json_encode($detalles),
                'retroalimentacion' => $feedbackGeneral,
                'tipo' => 'intento_quiz',
                'es_correcto' => ($calificacion >= 60)
            ]);

            return response()->json([
                'calificacion' => (int)$calificacion, // Forzamos entero para Flutter
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
    // 3. FUNCIONES AUXILIARES Y CONEXIÓN IA
    // ==========================================

    private function llamarGemini($prompt): array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) return ['success' => false, 'data' => 'Falta API Key'];

        // Usamos el modelo flash por rapidez
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

        try {
            // Http::withoutVerifying() ES VITAL PARA TU ERROR DE CONEXIÓN EN LOCALHOST
            $response = Http::withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . '?key=' . $apiKey, [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);

            if ($response->successful()) {
                $json = $response->json();
                if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                    return ['success' => true, 'data' => $json['candidates'][0]['content']['parts'][0]['text']];
                }
            }
            
            Log::error('Gemini Error: ' . $response->body());
            return ['success' => false, 'data' => 'Error IA: ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return ['success' => false, 'data' => 'Error conexión: ' . $e->getMessage()];
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
}