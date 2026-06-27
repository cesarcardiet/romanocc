<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Enviar enlace de recuperación de contraseña
     */
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El formato del correo electrónico no es válido',
            'email.exists' => 'No existe una cuenta con este correo electrónico'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No existe una cuenta con este correo electrónico'
            ], 404);
        }

        // Generar token único
        $token = Str::random(64);
        
        // Guardar token en la base de datos (crear tabla password_resets si no existe)
        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // URL del formulario web de reset (solo con token por seguridad)
        $resetUrl = url('/reset-password') . '?token=' . $token;

        try {
            // Enviar correo
            Mail::send('emails.password-reset', [
                'user' => $user,
                'resetUrl' => $resetUrl,
                'token' => $token
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                        ->subject('Recuperación de Contraseña - Romanocc');
            });

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un enlace de recuperación a tu correo electrónico'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error enviando correo de reset: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo. Por favor, intenta nuevamente.'
            ], 500);
        }
    }

    /**
     * Mostrar formulario de reset de contraseña
     */
    public function showResetForm(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return view('auth.reset-password')->with('error', 'Enlace inválido');
        }

        // Verificar que el token existe y no ha expirado (24 horas)
        $passwordReset = DB::table('password_resets')
            ->where('created_at', '>', Carbon::now()->subHours(24))
            ->get()
            ->first(function ($reset) use ($token) {
                return Hash::check($token, $reset->token);
            });

        if (!$passwordReset) {
            return view('auth.reset-password')->with('error', 'Enlace expirado o inválido');
        }

        return view('auth.reset-password', ['email' => $passwordReset->email]);
    }

    /**
     * Procesar reset de contraseña
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ], [
            'token.required' => 'Token requerido',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $token = $request->token;
        $password = $request->password;

        // Verificar que el token existe y no ha expirado
        $passwordReset = DB::table('password_resets')
            ->where('created_at', '>', Carbon::now()->subHours(24))
            ->get()
            ->first(function ($reset) use ($token) {
                return Hash::check($token, $reset->token);
            });

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'Token expirado o inválido'
            ], 400);
        }

        $email = $passwordReset->email;

        // Actualizar contraseña del usuario
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->password = Hash::make($password);
        $user->save();

        // Eliminar el token usado
        DB::table('password_resets')->where('email', $email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }
}
