<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:100',
                'email' => 'required|string|email|max:255|unique:users',
                'contrasena_hash' => 'required|string|min:6',
                'rol' => 'required|in:estudiante,administrador',
            ]);

            $user = User::create([
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                'contrasena_hash' => Hash::make($validated['contrasena_hash']),
                'rol' => $validated['rol'],
            ]);

            return response()->json([
                'message' => 'Usuario registrado exitosamente',
                'user' => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Login de usuario
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'contrasena_hash' => 'required|string',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['contrasena_hash'], $user->contrasena_hash)) {
                return response()->json(['message' => 'Credenciales inválidas'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login exitoso',
                'user' => $user,
                'token' => $token,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout exitoso']);
    }

    /**
     * Obtener usuario actual
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}

