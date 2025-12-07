<?php

namespace App\Http\Controllers;

use App\Models\Subtema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubtemaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Subtema::with(['contenidos', 'ejemplos', 'ejercicios'])->get()
        );
    }

     public function porTema($id)
    {
        $subtemas = Subtema::where('tema_id', $id)->get();
        return response()->json($subtemas);
    }

    public function show(Subtema $subtema): JsonResponse
    {
        return response()->json(
            $subtema->load(['contenidos', 'ejemplos', 'ejercicios'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        // Solo administradores pueden crear
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para crear subtemas'], 403);
        }

        $validated = $request->validate([
            'tema_id' => 'required|exists:temas,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'informacion' => 'nullable|string',
        ]);

        $subtema = Subtema::create($validated);
        return response()->json($subtema, 201);
    }

    public function update(Request $request, Subtema $subtema): JsonResponse
    {
        // Solo administradores pueden actualizar
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para actualizar subtemas'], 403);
        }

        $validated = $request->validate([
            'tema_id' => 'required|exists:temas,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'informacion' => 'nullable|string',
        ]);

        $subtema->update($validated);
        return response()->json($subtema);
    }

    public function destroy(Request $request, Subtema $subtema): JsonResponse
    {
        // Solo administradores pueden eliminar
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para eliminar subtemas'], 403);
        }

        $subtema->delete();
        return response()->json(null, 204);
    }
}
