<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::all());
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('avanceUsuarios'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'contrasena_hash' => 'required|string|min:8',
            'rol' => 'required|in:estudiante,profesor,admin',
        ]);

        $validated['contrasena_hash'] = bcrypt($validated['contrasena_hash']);
        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'rol' => 'required|in:estudiante,profesor,admin',
        ]);

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();
        return response()->json(null, 204);
    }
}
