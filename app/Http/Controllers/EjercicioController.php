<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EjercicioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Ejercicio::with('subtema')->get());
    }

    public function show(Ejercicio $ejercicio): JsonResponse
    {
        return response()->json($ejercicio->load('subtema'));
    }

    public function store(Request $request): JsonResponse
    {
        // Solo administradores pueden crear ejercicios
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para crear ejercicios'], 403);
        }

        $validated = $request->validate([
            'subtema_id' => 'required|exists:subtemas,id',
            'titulo' => 'required|string|max:255',
            'pregunta' => 'required|string',
            'solucion' => 'required|string',
            // OJO: Ajustado a minúsculas para coincidir con el Seeder
            'dificultad' => 'required|in:facil,medio,dificil,FACIL,MEDIO,DIFICIL', 
            'metadatos' => 'nullable|array',
            
            // --- NUEVOS CAMPOS AGREGADOS ---
            'tipo_interaccion' => 'nullable|string|max:30',
            'contenido_juego' => 'nullable|array', // Laravel valida JSON entrante como array
        ]);

        $ejercicio = Ejercicio::create($validated);
        return response()->json($ejercicio, 201);
    }

    public function update(Request $request, Ejercicio $ejercicio): JsonResponse
    {
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para actualizar ejercicios'], 403);
        }

        $validated = $request->validate([
            'subtema_id' => 'required|exists:subtemas,id',
            'titulo' => 'required|string|max:255',
            'pregunta' => 'required|string',
            'solucion' => 'required|string',
            'dificultad' => 'required|in:facil,medio,dificil,FACIL,MEDIO,DIFICIL',
            'metadatos' => 'nullable|array',
            
            // --- NUEVOS CAMPOS AGREGADOS ---
            'tipo_interaccion' => 'nullable|string|max:30',
            'contenido_juego' => 'nullable|array',
        ]);

        $ejercicio->update($validated);
        return response()->json($ejercicio);
    }

    public function destroy(Request $request, Ejercicio $ejercicio): JsonResponse
    {
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para eliminar ejercicios'], 403);
        }

        $ejercicio->delete();
        return response()->json(null, 204);
    }

    // --- ESTA ES LA FUNCIÓN QUE USA FLUTTER ---
    public function porSubtema($id)
    {
        // Ocultamos la 'solucion' por seguridad, 
        // PERO debemos incluir los campos nuevos para que el juego funcione
        $ejercicios = Ejercicio::where('subtema_id', $id)
            ->select(
                'id', 
                'subtema_id', 
                'titulo', 
                'pregunta', 
                'dificultad',
                'tipo_interaccion', // <--- ¡IMPORTANTE!
                'contenido_juego'   // <--- ¡IMPORTANTE!
            ) 
            ->get();

        return response()->json($ejercicios);
    }
}