<?php

namespace App\Http\Controllers;

use App\Models\Ejemplo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EjemploController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Ejemplo::with('subtema')->get());
    }

    public function show(Ejemplo $ejemplo): JsonResponse
    {
        return response()->json($ejemplo->load('subtema'));
    }

    public function store(Request $request): JsonResponse
    {
        // Solo administradores pueden crear ejemplos
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para crear ejemplos'], 403);
        }

        $validated = $request->validate([
            'subtema_id' => 'required|exists:subtemas,id',
            'titulo' => 'required|string|max:255',
            'cuerpo' => 'required|string',
        ]);

        $ejemplo = Ejemplo::create($validated);
        return response()->json($ejemplo, 201);
    }

    public function update(Request $request, Ejemplo $ejemplo): JsonResponse
    {
        // Solo administradores pueden actualizar ejemplos
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para actualizar ejemplos'], 403);
        }

        $validated = $request->validate([
            'subtema_id' => 'required|exists:subtemas,id',
            'titulo' => 'required|string|max:255',
            'cuerpo' => 'required|string',
        ]);

        $ejemplo->update($validated);
        return response()->json($ejemplo);
    }

    public function destroy(Request $request, Ejemplo $ejemplo): JsonResponse
    {
        // Solo administradores pueden eliminar ejemplos
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para eliminar ejemplos'], 403);
        }

        $ejemplo->delete();
        return response()->json(null, 204);
    }
}
