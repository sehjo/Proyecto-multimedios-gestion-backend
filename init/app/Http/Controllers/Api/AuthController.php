<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\HasPermissionRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\AuthResponseConst;
use App\Http\Responses\GlobalResponseConst;
use App\Enums\UserLogAction;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Support\AuditLogger;
use Dedoc\Scramble\Attributes\Response;
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
     * Log in.
     *
     * Validates the credentials and returns a Bearer token (Sanctum) along with
     * the user data (including their roles). The token must be sent in the
     * `Authorization: Bearer <token>` header on protected routes.
     *
     * @unauthenticated
     */
    #[Response(
        status: AuthResponseConst::LOGIN_SUCCESS['status'],
        description: AuthResponseConst::LOGIN_SUCCESS['description'],
        examples: [AuthResponseConst::LOGIN_SUCCESS['examples']],
    )]
    #[Response(
        status: AuthResponseConst::LOGIN_INVALID['status'],
        description: AuthResponseConst::LOGIN_INVALID['description'],
        examples: [AuthResponseConst::LOGIN_INVALID['examples']],
    )]
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
     * Log out.
     *
     * Revokes the authenticated user's current access token. After this, the
     * same token is no longer valid and any request using it returns 401.
     */
    #[Response(
        status: AuthResponseConst::LOGOUT_SUCCESS['status'],
        description: AuthResponseConst::LOGOUT_SUCCESS['description'],
        examples: [AuthResponseConst::LOGOUT_SUCCESS['examples']],
    )]
    #[Response(
        status: GlobalResponseConst::UNAUTHENTICATED['status'],
        description: GlobalResponseConst::UNAUTHENTICATED['description'],
        examples: [GlobalResponseConst::UNAUTHENTICATED['examples']],
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Check whether the authenticated user has a given permission.
     *
     * Lets the front-end ask if the user holds a permission (e.g. to show or
     * hide a button). The real authorization still lives in the route
     * middleware; this is only a convenience query.
     */
    #[Response(
        status: AuthResponseConst::HAS_PERMISSION_SUCCESS['status'],
        description: AuthResponseConst::HAS_PERMISSION_SUCCESS['description'],
        examples: [AuthResponseConst::HAS_PERMISSION_SUCCESS['examples']],
    )]
    #[Response(
        status: GlobalResponseConst::UNAUTHENTICATED['status'],
        description: GlobalResponseConst::UNAUTHENTICATED['description'],
        examples: [GlobalResponseConst::UNAUTHENTICATED['examples']],
    )]
    #[Response(
        status: GlobalResponseConst::VALIDATION_ERROR['status'],
        description: GlobalResponseConst::VALIDATION_ERROR['description'],
        examples: [GlobalResponseConst::VALIDATION_ERROR['examples']],
    )]
    public function hasPermission(HasPermissionRequest $request): JsonResponse
    {
        $permission = $request->validated()['permission'];

        return response()->json([
            'permission' => $permission,
            'granted'    => $request->user()->can($permission),
        ]);
    }

    /**
     * Request a password reset link.
     *
     * Generates a reset token and emails a link to the given address. For
     * security it always responds with a generic message, whether or not the
     * email exists (to avoid revealing which emails are registered).
     *
     * @unauthenticated
     */
    #[Response(
        status: AuthResponseConst::FORGOT_PASSWORD_SUCCESS['status'],
        description: AuthResponseConst::FORGOT_PASSWORD_SUCCESS['description'],
        examples: [AuthResponseConst::FORGOT_PASSWORD_SUCCESS['examples']],
    )]
    #[Response(
        status: GlobalResponseConst::VALIDATION_ERROR['status'],
        description: GlobalResponseConst::VALIDATION_ERROR['description'],
        examples: [GlobalResponseConst::VALIDATION_ERROR['examples']],
    )]
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
     * Reset the password using the token received by email.
     *
     * Validates the token (valid and not expired), updates the password and
     * revokes all of the user's active sessions. The reset token is deleted
     * after use.
     *
     * @unauthenticated
     */
    #[Response(
        status: AuthResponseConst::RESET_PASSWORD_SUCCESS['status'],
        description: AuthResponseConst::RESET_PASSWORD_SUCCESS['description'],
        examples: [AuthResponseConst::RESET_PASSWORD_SUCCESS['examples']],
    )]
    #[Response(
        status: AuthResponseConst::RESET_PASSWORD_INVALID['status'],
        description: AuthResponseConst::RESET_PASSWORD_INVALID['description'],
        examples: [AuthResponseConst::RESET_PASSWORD_INVALID['examples']],
    )]
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

        // Audit: self password reset. The password value is never stored.
        AuditLogger::userLog(UserLogAction::PasswordReset, $user, $user, [
            'password' => AuditLogger::maskedPassword(),
        ]);

        return response()->json([
            'message' => 'Tu contraseña se restableció correctamente. Inicia sesión con tu nueva contraseña.',
        ]);
    }
}
