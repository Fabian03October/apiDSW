<?php

namespace App\Http\Controllers;

use App\Models\ConsultaIA;
use App\Models\Ejercicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultaIAController extends Controller
{
    /**
     * Hacer una pregunta/duda al IA
     */
    public function pregunta(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pregunta' => 'required|string',
                'tema_id' => 'required|integer',
            ]);

            $usuario = $request->user();
            
            // Obtener contexto del tema
            $contexto = $this->obtenerContextoTema($validated['tema_id']);
            
            // Crear prompt con contexto
            $prompt = "Contexto educativo:\n{$contexto}\n\nPregunta del estudiante: {$validated['pregunta']}\n\nProporciona una respuesta educativa clara y detallada.";
            
            // Llamar a Gemini API
            $resultadoIA = $this->llamarGemini($prompt);
            $respuestaIA = $resultadoIA['success'] ? $resultadoIA['data'] : 'Error al obtener respuesta de IA';
            
            // Asegurar que sea string
            $respuestaIA = (string) $respuestaIA;

            $consulta = ConsultaIA::create([
                'usuario_id' => $usuario->id,
                'pregunta' => $validated['pregunta'],
                'respuesta_ia' => $respuestaIA,
                'tipo' => 'duda',
            ]);

            return response()->json([
                'message' => 'Pregunta procesada',
                'consulta' => $consulta,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error en pregunta:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generar pregunta basada en tema
     */
    public function generarPregunta(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tema_id' => 'required|integer',
                'tipo_pregunta' => 'required|string|in:opcion_multiple,verdadero_falso,respuesta_corta,desarrollo',
                'dificultad' => 'required|string|in:facil,intermedio,dificil'
            ]);

            $usuario = $request->user();
            
            // Obtener contexto del tema
            $contexto = $this->obtenerContextoTema($validated['tema_id']);
            
            // Crear prompt específico para generar preguntas
            $prompt = "Contexto educativo:\n{$contexto}\n\n";
            $prompt .= "Genera una pregunta de tipo: {$validated['tipo_pregunta']}\n";
            $prompt .= "Dificultad: {$validated['dificultad']}\n\n";
            
            switch($validated['tipo_pregunta']) {
                case 'opcion_multiple':
                    $prompt .= "Formato requerido JSON:\n";
                    $prompt .= "{\n";
                    $prompt .= "  \"pregunta\": \"texto de la pregunta\",\n";
                    $prompt .= "  \"opciones\": [\"a) opción 1\", \"b) opción 2\", \"c) opción 3\", \"d) opción 4\"],\n";
                    $prompt .= "  \"respuesta_correcta\": \"letra de la respuesta correcta (a, b, c, d)\",\n";
                    $prompt .= "  \"explicacion\": \"explicación de por qué es correcta\"\n";
                    $prompt .= "}";
                    break;
                    
                case 'verdadero_falso':
                    $prompt .= "Formato requerido JSON:\n";
                    $prompt .= "{\n";
                    $prompt .= "  \"pregunta\": \"afirmación para evaluar\",\n";
                    $prompt .= "  \"respuesta_correcta\": \"verdadero\" o \"falso\",\n";
                    $prompt .= "  \"explicacion\": \"explicación de la respuesta\"\n";
                    $prompt .= "}";
                    break;
                    
                case 'respuesta_corta':
                    $prompt .= "Formato requerido JSON:\n";
                    $prompt .= "{\n";
                    $prompt .= "  \"pregunta\": \"pregunta que requiere respuesta breve\",\n";
                    $prompt .= "  \"respuesta_esperada\": \"respuesta modelo corta\",\n";
                    $prompt .= "  \"palabras_clave\": [\"palabra1\", \"palabra2\", \"palabra3\"]\n";
                    $prompt .= "}";
                    break;
                    
                case 'desarrollo':
                    $prompt .= "Formato requerido JSON:\n";
                    $prompt .= "{\n";
                    $prompt .= "  \"pregunta\": \"pregunta que requiere desarrollo extenso\",\n";
                    $prompt .= "  \"puntos_clave\": [\"punto 1\", \"punto 2\", \"punto 3\"],\n";
                    $prompt .= "  \"criterios_evaluacion\": [\"criterio 1\", \"criterio 2\"]\n";
                    $prompt .= "}";
                    break;
            }
            
            $prompt .= "\n\nResponde SOLO con el JSON válido, sin texto adicional.";
            
            // Llamar a Gemini API
            $resultadoIA = $this->llamarGemini($prompt);
            
            if ($resultadoIA['success']) {
                // Limpiar la respuesta de Gemini (remover bloques de código markdown)
                $respuestaLimpia = $resultadoIA['data'];
                
                // Remover bloques de código markdown si existen
                $respuestaLimpia = preg_replace('/```json\s*/', '', $respuestaLimpia);
                $respuestaLimpia = preg_replace('/```\s*$/', '', $respuestaLimpia);
                $respuestaLimpia = trim($respuestaLimpia);
                
                // Intentar parsear el JSON de la respuesta limpia
                $preguntaGenerada = json_decode($respuestaLimpia, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Guardar la pregunta generada en consultas
                    $consulta = ConsultaIA::create([
                        'usuario_id' => $usuario->id,
                        'pregunta' => 'Generar pregunta de ' . $validated['tipo_pregunta'] . ' - ' . $validated['dificultad'],
                        'respuesta_ia' => $respuestaLimpia,
                        'tipo' => 'duda',
                    ]);

                    return response()->json([
                        'message' => 'Pregunta generada exitosamente',
                        'pregunta_generada' => $preguntaGenerada,
                        'tipo' => $validated['tipo_pregunta'],
                        'dificultad' => $validated['dificultad'],
                        'consulta_id' => $consulta->id
                    ], 201);
                } else {
                    return response()->json([
                        'error' => 'Error al parsear respuesta de IA',
                        'respuesta_cruda' => $resultadoIA['data'],
                        'respuesta_limpia' => $respuestaLimpia,
                        'json_error' => json_last_error_msg()
                    ], 422);
                }
            } else {
                return response()->json([
                    'error' => 'Error al generar pregunta: ' . $resultadoIA['data']
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error al generar pregunta:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Responder a una pregunta generada por la IA
     */
    public function responderPreguntaIA(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'consulta_id' => 'required|integer', // ID de la consulta donde se generó la pregunta
                'respuesta_estudiante' => 'required|string',
            ]);

            $usuario = $request->user();
            
            // Obtener la consulta original donde se generó la pregunta
            $consultaOriginal = ConsultaIA::where('id', $validated['consulta_id'])
                ->where('usuario_id', $usuario->id)
                ->first();

            if (!$consultaOriginal) {
                return response()->json([
                    'error' => 'Consulta no encontrada o no tienes permisos para responder'
                ], 404);
            }

            // Obtener la pregunta generada desde la respuesta_ia de la consulta original
            $preguntaGenerada = json_decode($consultaOriginal->respuesta_ia, true);
            
            if (!$preguntaGenerada || !isset($preguntaGenerada['pregunta'])) {
                return response()->json([
                    'error' => 'No se pudo obtener la pregunta original'
                ], 422);
            }

            // Crear prompt para evaluar la respuesta del estudiante
            $prompt = "Eres un evaluador educativo. Aquí está la pregunta que se le hizo al estudiante:\n\n";
            $prompt .= "PREGUNTA: " . $preguntaGenerada['pregunta'] . "\n\n";
            
            // Agregar información específica según el tipo de pregunta
            if (isset($preguntaGenerada['opciones'])) {
                $prompt .= "OPCIONES:\n";
                foreach ($preguntaGenerada['opciones'] as $opcion) {
                    $prompt .= $opcion . "\n";
                }
                $prompt .= "\nRESPUESTA CORRECTA: " . $preguntaGenerada['respuesta_correcta'] . "\n";
                $prompt .= "EXPLICACIÓN CORRECTA: " . $preguntaGenerada['explicacion'] . "\n\n";
            } elseif (isset($preguntaGenerada['respuesta_correcta'])) {
                $prompt .= "RESPUESTA CORRECTA: " . $preguntaGenerada['respuesta_correcta'] . "\n";
                if (isset($preguntaGenerada['explicacion'])) {
                    $prompt .= "EXPLICACIÓN: " . $preguntaGenerada['explicacion'] . "\n";
                }
            } elseif (isset($preguntaGenerada['respuesta_esperada'])) {
                $prompt .= "RESPUESTA ESPERADA: " . $preguntaGenerada['respuesta_esperada'] . "\n";
                if (isset($preguntaGenerada['palabras_clave'])) {
                    $prompt .= "PALABRAS CLAVE: " . implode(', ', $preguntaGenerada['palabras_clave']) . "\n";
                }
            } elseif (isset($preguntaGenerada['puntos_clave'])) {
                $prompt .= "PUNTOS CLAVE A EVALUAR:\n";
                foreach ($preguntaGenerada['puntos_clave'] as $punto) {
                    $prompt .= "- " . $punto . "\n";
                }
            }
            
            $prompt .= "\nRESPUESTA DEL ESTUDIANTE: " . $validated['respuesta_estudiante'] . "\n\n";
            
            $prompt .= "Evalúa la respuesta del estudiante y responde en formato JSON con:\n";
            $prompt .= "{\n";
            $prompt .= '  "es_correcto": true/false,' . "\n";
            $prompt .= '  "puntuacion": numero del 0 al 100,' . "\n";
            $prompt .= '  "evaluacion": "explicación detallada de la evaluación",' . "\n";
            $prompt .= '  "retroalimentacion": "consejos específicos para mejorar",' . "\n";
            $prompt .= '  "aspectos_correctos": ["aspecto1", "aspecto2"],' . "\n";
            $prompt .= '  "aspectos_a_mejorar": ["aspecto1", "aspecto2"]' . "\n";
            $prompt .= "}\n\n";
            $prompt .= "Responde SOLO con el JSON, sin texto adicional.";

            // Llamar a Gemini para evaluar
            $resultadoIA = $this->llamarGemini($prompt);
            
            if ($resultadoIA['success']) {
                // Limpiar respuesta de bloques markdown
                $respuestaLimpia = $resultadoIA['data'];
                $respuestaLimpia = preg_replace('/```json\s*/', '', $respuestaLimpia);
                $respuestaLimpia = preg_replace('/```\s*$/', '', $respuestaLimpia);
                $respuestaLimpia = trim($respuestaLimpia);
                
                $evaluacion = json_decode($respuestaLimpia, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Guardar la respuesta del estudiante y la evaluación
                    $consultaRespuesta = ConsultaIA::create([
                        'usuario_id' => $usuario->id,
                        'pregunta' => $validated['respuesta_estudiante'],
                        'respuesta_ia' => $respuestaLimpia,
                        'retroalimentacion' => $evaluacion['retroalimentacion'] ?? '',
                        'tipo' => 'respuesta_ejercicio',
                        'es_correcto' => $evaluacion['es_correcto'] ?? false,
                    ]);

                    return response()->json([
                        'message' => 'Respuesta evaluada exitosamente',
                        'evaluacion' => $evaluacion,
                        'pregunta_original' => $preguntaGenerada,
                        'consulta_respuesta_id' => $consultaRespuesta->id,
                        'consulta_pregunta_id' => $consultaOriginal->id
                    ], 201);
                } else {
                    return response()->json([
                        'error' => 'Error al parsear evaluación de IA',
                        'respuesta_cruda' => $resultadoIA['data'],
                        'respuesta_limpia' => $respuestaLimpia
                    ], 422);
                }
            } else {
                return response()->json([
                    'error' => 'Error al evaluar respuesta: ' . $resultadoIA['data']
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error al responder pregunta IA:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verificar respuesta de ejercicio
     */
    public function verificarEjercicio(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ejercicio_id' => 'required|integer',
                'respuesta_estudiante' => 'required|string',
            ]);

            $usuario = $request->user();
            $ejercicio = Ejercicio::findOrFail($validated['ejercicio_id']);

            // Obtener contexto del ejercicio
            $contexto = $this->obtenerContextoEjercicio($ejercicio);

            // Llamar a Gemini para verificar respuesta
            $resultado = $this->verificarRespuestaConGemini(
                $ejercicio,
                $validated['respuesta_estudiante'],
                $contexto
            );

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
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener historial de consultas del usuario
     */
    public function historial(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $consultas = ConsultaIA::where('usuario_id', $usuario->id)
            ->with('ejercicio')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($consultas);
    }

    /**
     * Test simple sin IA (para debugging)
     */
    public function testSinIA(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pregunta' => 'required|string',
                'tema_id' => 'required|integer',
            ]);

            $usuario = $request->user();
            
            $consulta = ConsultaIA::create([
                'usuario_id' => $usuario->id,
                'pregunta' => $validated['pregunta'],
                'respuesta_ia' => 'Respuesta de prueba sin IA',
                'tipo' => 'duda',
            ]);

            return response()->json([
                'message' => 'Test exitoso',
                'consulta' => $consulta,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Probar conexión con Gemini (temporal)
     */
    public function probarGemini(Request $request): JsonResponse
    {
        $apiKey = 'AIzaSyD2eREhfw4U1_X5p_xa5AcorvGC_E5mfUk';
        
        // Probar con diferentes versiones y modelos
        $modelos = [
            'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
            'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent'
        ];

        $resultados = [];
        
        foreach ($modelos as $url) {
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => '¿Cuánto es 2+2?',
                            ]
                        ]
                    ]
                ]
            ];

            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()->post(
                    $url . '?key=' . $apiKey,
                    $payload
                );

                $resultados[] = [
                    'url' => $url,
                    'status' => $response->status(),
                    'success' => $response->successful(),
                    'body' => $response->body()
                ];
            } catch (\Exception $e) {
                $resultados[] = [
                    'url' => $url,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json($resultados);
    }

    /**
     * Obtener contexto del tema
     */
    private function obtenerContextoTema($temaId): string
    {
        // Obtener tema con sus contenidos, ejemplos y ejercicios
        $tema = \App\Models\Tema::with([
            'subtemas.contenidos',
            'subtemas.ejemplos',
            'subtemas.ejercicios'
        ])->find($temaId);

        if (!$tema) {
            return '';
        }

        $contexto = "Tema: {$tema->titulo}\n";
        $contexto .= "Descripción: {$tema->descripcion}\n\n";

        foreach ($tema->subtemas as $subtema) {
            $contexto .= "Subtema: {$subtema->titulo}\n";
            $contexto .= "Descripción: {$subtema->descripcion}\n";

            foreach ($subtema->contenidos as $contenido) {
                $contexto .= "Contenido: {$contenido->titulo}\n{$contenido->cuerpo}\n";
            }

            foreach ($subtema->ejemplos as $ejemplo) {
                $contexto .= "Ejemplo: {$ejemplo->titulo}\n{$ejemplo->cuerpo}\n";
            }

            $contexto .= "\n";
        }

        return $contexto;
    }

    /**
     * Obtener contexto del ejercicio
     */
    private function obtenerContextoEjercicio($ejercicio): string
    {
        $subtema = $ejercicio->subtema;
        
        $contexto = "Ejercicio: {$ejercicio->titulo}\n";
        $contexto .= "Pregunta: {$ejercicio->pregunta}\n";
        $contexto .= "Solución esperada: {$ejercicio->solucion}\n";
        $contexto .= "Dificultad: {$ejercicio->dificultad}\n\n";
        
        if ($subtema) {
            $contexto .= "Subtema relacionado: {$subtema->titulo}\n";
            $contexto .= "Descripción: {$subtema->descripcion}\n";

            $contenido = $subtema->contenidos->first();
            if ($contenido) {
                $contexto .= "Contenido base: {$contenido->cuerpo}\n";
            }
        }

        return $contexto;
    }

    /**
     * Llamar a la API de Gemini
     */
    private function llamarGemini($prompt): array
    {
        $apiKey = env('GEMINI_API_KEY');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt,
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->post(
                $url . '?key=' . $apiKey,
                $data
            );

            if ($response->successful()) {
                $responseData = $response->json();
                $texto = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'No se pudo obtener respuesta';
                
                return [
                    'success' => true,
                    'data' => $texto
                ];
            }

            return [
                'success' => false,
                'data' => 'Error: ' . $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => 'Error al conectar con IA: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar respuesta con Gemini
     */
    private function verificarRespuestaConGemini($ejercicio, $respuestaEstudiante, $contexto): array
    {
        $apiKey = env('GEMINI_API_KEY');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

        $prompt = "Eres un evaluador de ejercicios educativo. Aquí está el contexto:\n\n{$contexto}\n\n";
        $prompt .= "Respuesta del estudiante: {$respuestaEstudiante}\n\n";
        $prompt .= "Por favor evalúa la respuesta. Responde en formato JSON con las siguientes claves:\n";
        $prompt .= "- \"es_correcto\": boolean (true si es correcta, false si es incorrecta)\n";
        $prompt .= "- \"evaluacion\": string (explicación breve de si es correcta o no)\n";
        $prompt .= "- \"retroalimentacion\": string (sugerencias de mejora o felicitaciones)\n";
        $prompt .= "Responde SOLO con el JSON, sin explicaciones adicionales.";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt,
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->post(
                $url . '?key=' . $apiKey,
                $payload
            );

            if ($response->successful()) {
                $data = $response->json();
                $textoRespuesta = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                
                // Extraer JSON de la respuesta
                $jsonMatch = preg_match('/\{.*\}/s', $textoRespuesta, $matches);
                if ($jsonMatch) {
                    $json = json_decode($matches[0], true);
                    return [
                        'es_correcto' => $json['es_correcto'] ?? false,
                        'evaluacion' => $json['evaluacion'] ?? '',
                        'retroalimentacion' => $json['retroalimentacion'] ?? '',
                    ];
                }
            }

            return [
                'es_correcto' => false,
                'evaluacion' => 'Error al evaluar: ' . $response->status(),
                'retroalimentacion' => 'Intenta de nuevo más tarde',
            ];
        } catch (\Exception $e) {
            return [
                'es_correcto' => false,
                'evaluacion' => 'Error al contactar con IA',
                'retroalimentacion' => $e->getMessage(),
            ];
        }
    }
}

