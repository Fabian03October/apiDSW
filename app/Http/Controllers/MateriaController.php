<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Materia::with('temas')->get());
    }

    public function show(Materia $materia): JsonResponse
    {
        return response()->json($materia->load('temas.subtemas.contenidos'));
    }

    public function store(Request $request): JsonResponse
    {
        // Solo administradores pueden crear
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para crear materias'], 403);
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $materia = Materia::create($validated);
        return response()->json($materia, 201);
    }

    public function update(Request $request, Materia $materia): JsonResponse
    {
        // Solo administradores pueden actualizar
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para actualizar materias'], 403);
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $materia->update($validated);
        return response()->json($materia);
    }

    public function destroy(Request $request, Materia $materia): JsonResponse
    {
        // Solo administradores pueden eliminar
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para eliminar materias'], 403);
        }

        $materia->delete();
        return response()->json(null, 204);
    }
}

