<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user with phone and password
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('phone', 'password');
        
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'phone' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $user = Auth::user();
        
        if (!$user->isActive()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'phone' => ['Su cuenta está inactiva. Contacte al administrador.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'type' => $user->type_label,
                    'status' => $user->status_label,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Register new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'accepted_terms' => true,
            'type' => 'public',
            'status' => 'active',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'type' => $user->type_label,
                    'status' => $user->status_label,
                ],
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso',
        ], 200);
    }

    /**
     * Get authenticated user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'type' => $user->type_label,
                    'status' => $user->status_label,
                ],
            ],
        ], 200);
    }

    /**
     * Update authenticated user profile
     * Supports both mobile app (partial updates) and admin panel (full updates)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Determine validation rules based on request context
        $validationRules = [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
            'current_password' => 'required_with:new_password|string',
            'new_password' => 'sometimes|string|min:6|confirmed',
        ];

        // Custom error messages
        $errorMessages = [
            'name.required' => 'El nombre es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'phone.required' => 'El teléfono es requerido.',
            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'phone.unique' => 'Este número de teléfono ya está registrado.',
            'current_password.required_with' => 'La contraseña actual es requerida para cambiar la contraseña.',
            'current_password.string' => 'La contraseña actual debe ser una cadena de texto.',
            'new_password.string' => 'La nueva contraseña debe ser una cadena de texto.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ];

        $request->validate($validationRules, $errorMessages);

        // Prepare update data
        $updateData = [];

        // Update name if provided
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }

        // Update phone if provided
        if ($request->has('phone')) {
            $updateData['phone'] = $request->phone;
        }

        // Update password if provided
        if ($request->has('new_password')) {
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['La contraseña actual es incorrecta.'],
                ]);
            }
            
            $updateData['password'] = Hash::make($request->new_password);
        }

        // Update user if there are changes
        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'type' => $user->type_label,
                    'status' => $user->status_label,
                ],
            ],
        ], 200);
    }
}
