<?php

namespace App\Http\Controllers;

use App\Models\Contenido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContenidoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Contenido::with('subtema')->get());
    }

    public function show(Contenido $contenido): JsonResponse
    {
        return response()->json($contenido->load('subtema'));
    }

    public function store(Request $request): JsonResponse
    {
        // Solo administradores pueden crear
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para crear contenido'], 403);
        }

        $validated = $request->validate([
            'subtema_id' => 'required|exists:subtemas,id',
            'titulo' => 'required|string|max:255',
            'cuerpo' => 'required|string',
            'tipo_contenido' => 'required|in:TEXTO,VIDEO,IMAGEN,DOCUMENTO',
        ]);

        $contenido = Contenido::create($validated);
        return response()->json($contenido, 201);
    }

    public function update(Request $request, Contenido $contenido): JsonResponse
    {
        // Solo administradores pueden actualizar
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para actualizar contenido'], 403);
        }

        $validated = $request->validate([
            'subtema_id' => 'required|exists:subtemas,id',
            'titulo' => 'required|string|max:255',
            'cuerpo' => 'required|string',
            'tipo_contenido' => 'required|in:TEXTO,VIDEO,IMAGEN,DOCUMENTO',
        ]);

        $contenido->update($validated);
        return response()->json($contenido);
    }

    public function destroy(Request $request, Contenido $contenido): JsonResponse
    {
        // Solo administradores pueden eliminar
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para eliminar contenido'], 403);
        }

        $contenido->delete();
        return response()->json(null, 204);
    }
}
