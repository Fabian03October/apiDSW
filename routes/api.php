<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvanceUsuarioController;
use App\Http\Controllers\ContenidoController;
use App\Http\Controllers\ConsultaIAController;
use App\Http\Controllers\EjemploController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\SubtemaController;
use App\Http\Controllers\TemaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request; 

// Rutas de autenticación (públicas)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/ver-modelos-disponibles', function () {
    $apiKey = trim(env('GEMINI_API_KEY'));
    
    // Consultamos la lista oficial de modelos
    $response = \Illuminate\Support\Facades\Http::withoutVerifying()
        ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
    
    return $response->json();
});


// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/materias/{id}/temas', [TemaController::class, 'porMateria']);
    Route::get('/temas/{id}/subtemas', [SubtemaController::class, 'porTema']);
    Route::get('/subtemas/{id}/contenidos', [ContenidoController::class, 'porSubtema']);
    // Rutas para Quizzes y Conjuntos
    Route::post('/ia/generar-cuestionario', [ConsultaIAController::class, 'generarCuestionario']);
    Route::post('/ia/responder-cuestionario', [ConsultaIAController::class, 'responderCuestionario']);
    Route::post('/ia/evaluar-conjunto-ejercicios', [ConsultaIAController::class, 'evaluarConjuntoEjercicios']);
    Route::get('/subtemas/{id}/ejercicios', [EjercicioController::class, 'porSubtema']);
    Route::post('/ia/evaluar-lote-ejercicios', [ConsultaIAController::class, 'evaluarConjuntoEjercicios']);
    Route::post('/ia/evaluar-quiz', [App\Http\Controllers\ConsultaIAController::class, 'evaluarQuiz']); 

    // Rutas de Materias
    Route::apiResource('materias', MateriaController::class);

    // Rutas de Temas
    Route::apiResource('temas', TemaController::class);

    // Rutas de Subtemas
    Route::apiResource('subtemas', SubtemaController::class);

    // Rutas de Contenidos
    Route::apiResource('contenidos', ContenidoController::class);

    // Rutas de Ejemplos
    Route::apiResource('ejemplos', EjemploController::class);

    // Rutas de Ejercicios
    Route::apiResource('ejercicios', EjercicioController::class);

    // Rutas de Usuarios
    Route::apiResource('usuarios', UserController::class);

    // Rutas de Avance de Usuarios (estudiantes guardan progreso)
    Route::apiResource('avance-usuarios', AvanceUsuarioController::class);
    Route::get('avance-usuarios/usuario/{usuarioId}', [AvanceUsuarioController::class, 'porUsuario']);

    // Rutas de IA (Gemini) para estudiantes
    Route::post('/ia/pregunta', [ConsultaIAController::class, 'pregunta']);
    Route::post('/ia/generar-pregunta', [ConsultaIAController::class, 'generarPregunta']);
    Route::post('/ia/responder-pregunta-ia', [ConsultaIAController::class, 'responderPreguntaIA']);
    Route::post('/ia/verificar-ejercicio', [ConsultaIAController::class, 'verificarEjercicio']);
    Route::get('/ia/historial', [ConsultaIAController::class, 'historial']);
    
    // Rutas temporales para debugging
    Route::post('/ia/test-sin-ia', [ConsultaIAController::class, 'testSinIA']);
    Route::get('/ia/probar', [ConsultaIAController::class, 'probarGemini']);
});

// Ruta de prueba SIN middleware (temporal)
Route::post('/test-ia-sin-auth', function (Request $request) {
    try {
        \Log::info('Test sin auth - Request data:', $request->all());
        
        $validated = $request->validate([
            'pregunta' => 'required|string',
            'tema_id' => 'required|integer',
        ]);
        
        \Log::info('Test sin auth - Validación exitosa:', $validated);
        
        $consulta = \App\Models\ConsultaIA::create([
            'usuario_id' => 1, // Usuario fijo para testing
            'pregunta' => $validated['pregunta'],
            'respuesta_ia' => 'Respuesta de prueba sin auth',
            'tipo' => 'duda',
        ]);
        
        \Log::info('Test sin auth - Consulta creada:', $consulta->toArray());
        
        return response()->json([
            'message' => 'Test sin auth exitoso',
            'consulta' => $consulta,
        ], 201);
        
    } catch (\Exception $e) {
        \Log::error('Test sin auth - Error:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
