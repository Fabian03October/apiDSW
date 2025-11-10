<?php

namespace App\Http\Controllers;

use App\Models\AvanceUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvanceUsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        $usuario = auth()->user();
        
        // Administradores ven todo, estudiantes solo su progreso
        if ($usuario->rol === 'administrador') {
            return response()->json(
                AvanceUsuario::with(['usuario', 'subtema'])->get()
            );
        } else {
            return response()->json(
                AvanceUsuario::where('usuario_id', $usuario->id)
                    ->with(['subtema'])->get()
            );
        }
    }

    public function show(AvanceUsuario $avanceUsuario): JsonResponse
    {
        $usuario = auth()->user();
        
        // Verificar que el usuario solo vea su progreso o sea admin
        if ($usuario->rol !== 'administrador' && $avanceUsuario->usuario_id !== $usuario->id) {
            return response()->json(['message' => 'No tiene permisos para ver este progreso'], 403);
        }

        return response()->json(
            $avanceUsuario->load(['usuario', 'subtema'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $usuario = auth()->user();

        $validated = $request->validate([
            'subtema_id' => 'required|exists:subtemas,id',
            'completado' => 'nullable|boolean',
            'puntacion' => 'nullable|integer',
            'notas' => 'nullable|string',
        ]);

        // Los estudiantes solo pueden guardar su propio progreso
        $validated['usuario_id'] = $usuario->id;
        
        if ($validated['completado'] ?? false) {
            $validated['fecha_completado'] = now();
        }

        // Verificar si ya existe progreso para este subtema
        $avanceExistente = AvanceUsuario::where('usuario_id', $usuario->id)
            ->where('subtema_id', $validated['subtema_id'])
            ->first();

        if ($avanceExistente) {
            $avanceExistente->update($validated);
            return response()->json($avanceExistente, 200);
        }

        $avanceUsuario = AvanceUsuario::create($validated);
        return response()->json($avanceUsuario, 201);
    }

    public function update(Request $request, AvanceUsuario $avanceUsuario): JsonResponse
    {
        $usuario = auth()->user();
        
        // Verificar permisos
        if ($usuario->rol !== 'administrador' && $avanceUsuario->usuario_id !== $usuario->id) {
            return response()->json(['message' => 'No tiene permisos para actualizar este progreso'], 403);
        }

        $validated = $request->validate([
            'completado' => 'nullable|boolean',
            'puntacion' => 'nullable|integer',
            'notas' => 'nullable|string',
        ]);

        if ($validated['completado'] ?? false) {
            $validated['fecha_completado'] = now();
        }

        $avanceUsuario->update($validated);
        return response()->json($avanceUsuario);
    }

    public function destroy(Request $request, AvanceUsuario $avanceUsuario): JsonResponse
    {
        $usuario = auth()->user();
        
        // Solo administradores pueden eliminar progreso
        if ($usuario->rol !== 'administrador') {
            return response()->json(['message' => 'No tiene permisos para eliminar progreso'], 403);
        }

        $avanceUsuario->delete();
        return response()->json(null, 204);
    }

    public function porUsuario(int $usuarioId): JsonResponse
    {
        $usuario = auth()->user();
        
        // Verificar permisos: admin ve cualquier usuario, estudiante solo el suyo
        if ($usuario->rol !== 'administrador' && $usuarioId !== $usuario->id) {
            return response()->json(['message' => 'No tiene permisos para ver este progreso'], 403);
        }

        $avances = AvanceUsuario::where('usuario_id', $usuarioId)
            ->with('subtema')
            ->get();
        return response()->json($avances);
    }
}
