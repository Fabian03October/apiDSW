<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                // Aceptamos 'password' desde el frontend, es el estándar
                'password' => 'required|string|min:6', 
                'rol' => 'required|in:estudiante,administrador',
            ]);

            $user = User::create([
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                // IMPORTANTE: No usamos Hash::make() aquí porque tu Modelo User
                // ya tiene 'casts' => 'hashed'. Laravel lo hace solo.
                'password' => $validated['password'], 
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
            // 1. Validamos 'password' (así lo envía Flutter y es el estándar)
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            // 2. Preparamos las credenciales para Auth::attempt
            // IMPORTANTE: La llave DEBE ser 'password' para que Laravel sepa que esa es la contraseña.
            // Laravel buscará el usuario por 'email', obtendrá su hash de la BD (usando getAuthPassword del modelo)
            // y lo comparará con lo que pasamos aquí en 'password'.
            $credentials = [
                'email' => $request->email,
                'password' => $request->password 
            ];

            // 3. Intentamos loguear
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // 4. Éxito: Generar Token
            $user = Auth::user();
            // Borramos tokens viejos para mantener limpio (opcional)
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login exitoso',
                'user' => $user,
                'token' => $token,
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Esto nos ayudará a ver si hay otro error interno
            return response()->json(['message' => 'Error en servidor: ' . $e->getMessage()], 500);
        }
    }

    // ... logout y me se quedan igual ...
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout exitoso']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}