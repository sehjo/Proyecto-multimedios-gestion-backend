<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate the user and issue a Sanctum token.
     *
     * POST /login
     * Body: { email, password }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    /**
     * Revoke the current access token.
     *
     * POST /logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Generate a password reset token and send it via email.
     *
     * POST /auth/forgot-password
     * Body: { email }
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Always return a generic success response to prevent user enumeration.
        if (! $user) {
            return response()->json([
                'message' => 'Si este correo está registrado, recibirás un enlace para restablecer tu contraseña en breve.',
            ]);
        }

        // Delete any existing tokens for this email before creating a new one.
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Generate a secure random plain-text token and store its hash.
        $plainToken  = Str::random(64);
        $hashedToken = hash('sha256', $plainToken);

        DB::table('password_reset_tokens')->insert([
            'email'      => $user->email,
            'token'      => $hashedToken,
            'created_at' => now(),
        ]);

        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $resetUrl    = $frontendUrl . '/reset-password?' . http_build_query(['token' => $plainToken]);

        try {
            Mail::to($user->email)->send(new PasswordResetMail($resetUrl));
        } catch (\Throwable $e) {
            // Log the mail error but never expose it to the client.
            \Illuminate\Support\Facades\Log::error('Password reset mail failed', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Si este correo está registrado, recibirás un enlace para restablecer tu contraseña en breve.',
        ]);
    }

    /**
     * Validate the reset token and update the user's password.
     *
     * POST /auth/reset-password
     * Body: { token, password }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $hashedToken = hash('sha256', $request->token);

        $record = DB::table('password_reset_tokens')
            ->where('token', $hashedToken)
            ->first();

        if (! $record) {
            return response()->json([
                'message' => 'Este token de restablecimiento de contraseña no es válido.',
            ], 422);
        }

        // This tokens expire after 60 minutes.
        $expireMinutes = config('auth.passwords.tokens.expire', 60);
        if (Carbon::parse($record->created_at)->addMinutes($expireMinutes)->lte(now())) {
            DB::table('password_reset_tokens')->where('token', $hashedToken)->delete();

            return response()->json([
                'message' => 'Este token de restablecimiento de contraseña ha expirado. Por favor, solicita uno nuevo.',
            ], 422);
        }

        $user = User::where('email', $record->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'No se encontró usuario para token de restablecimiento.',
            ], 422);
        }

        // Update the password and revoke all existing API tokens.
        $user->forceFill(['password' => Hash::make($request->password)])->save();
        $user->tokens()->delete();

        // Remove the used reset token.
        DB::table('password_reset_tokens')->where('token', $hashedToken)->delete();

        return response()->json([
            'message' => 'Tu contraseña se restableció correctamente. Inicia sesión con tu nueva contraseña.',
        ]);
    }
}
