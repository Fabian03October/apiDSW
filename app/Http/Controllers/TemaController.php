<?php

namespace App\Http\Controllers;

use App\Models\Tema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Tema::with('subtemas')->get());
    }

    public function show(Tema $tema): JsonResponse
    {
        return response()->json($tema->load('subtemas.contenidos', 'subtemas.ejemplos', 'subtemas.ejercicios'));
    }

    public function store(Request $request): JsonResponse
    {
        // Solo administradores pueden crear
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para crear temas'], 403);
        }

        $validated = $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $tema = Tema::create($validated);
        return response()->json($tema, 201);
    }

    public function update(Request $request, Tema $tema): JsonResponse
    {
        // Solo administradores pueden actualizar
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para actualizar temas'], 403);
        }

        $validated = $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $tema->update($validated);
        return response()->json($tema);
    }

    public function destroy(Request $request, Tema $tema): JsonResponse
    {
        // Solo administradores pueden eliminar
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para eliminar temas'], 403);
        }

        $tema->delete();
        return response()->json(null, 204);
    }

    public function porMateria($materiaId): JsonResponse
    {
        $temas = Tema::with('subtemas')->where('materia_id', $materiaId)->get();
        return response()->json($temas);
    }
}
