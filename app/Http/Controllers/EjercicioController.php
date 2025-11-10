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
            'dificultad' => 'required|in:FACIL,MEDIO,DIFICIL',
            'metadatos' => 'nullable|array',
        ]);

        $ejercicio = Ejercicio::create($validated);
        return response()->json($ejercicio, 201);
    }

    public function update(Request $request, Ejercicio $ejercicio): JsonResponse
    {
        // Solo administradores pueden actualizar ejercicios
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para actualizar ejercicios'], 403);
        }

        $validated = $request->validate([
            'subtema_id' => 'required|exists:subtemas,id',
            'titulo' => 'required|string|max:255',
            'pregunta' => 'required|string',
            'solucion' => 'required|string',
            'dificultad' => 'required|in:FACIL,MEDIO,DIFICIL',
            'metadatos' => 'nullable|array',
        ]);

        $ejercicio->update($validated);
        return response()->json($ejercicio);
    }

    public function destroy(Request $request, Ejercicio $ejercicio): JsonResponse
    {
        // Solo administradores pueden eliminar ejercicios
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para eliminar ejercicios'], 403);
        }

        $ejercicio->delete();
        return response()->json(null, 204);
    }
}
