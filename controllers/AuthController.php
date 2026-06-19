<?php

class AuthController
{
    public function login(Request $request): void
    {
        $errors = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        $result = Auth::attempt($request->input('email'), $request->input('password'));

        if (!$result) {
            Response::validationError(['email' => ['Las credenciales proporcionadas son incorrectas.']]);

            return;
        }

        Response::json([
            'token' => $result['token'],
            'user' => UserResource::toArray($result['user']),
        ]);
    }

    public function me(Request $request): void
    {
        $user = Auth::user($request);

        if (!$user) {
            Response::json(['message' => 'Unauthenticated.'], 401);

            return;
        }

        Response::json(UserResource::toArray($user));
    }

    public function logout(Request $request): void
    {
        $user = Auth::user($request);

        if (!$user) {
            Response::json(['message' => 'Unauthenticated.'], 401);

            return;
        }

        Auth::logout($request);

        Response::json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function forgotPassword(Request $request): void
    {
        $errors = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        $generic = 'Si este correo está registrado, recibirás un enlace para restablecer tu contraseña en breve.';
        $user = UserRepository::findByEmail($request->input('email'));

        if (!$user) {
            Response::json(['message' => $generic]);

            return;
        }

        PasswordResetRepository::deleteByEmail($user->getEmail());

        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        PasswordResetRepository::create($user->getEmail(), $hashedToken);

        $resetUrl = config('app.frontend_url') . '/reset-password?' . http_build_query(['token' => $plainToken]);

        try {
            Mailer::sendPasswordReset($user->getEmail(), $resetUrl);
        } catch (\Throwable $e) {
            // Never expose mail delivery failures to the client.
        }

        Response::json(['message' => $generic]);
    }

    public function resetPassword(Request $request): void
    {
        $errors = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        $hashedToken = hash('sha256', $request->input('token'));
        $record = PasswordResetRepository::findByHash($hashedToken);

        if (!$record) {
            Response::json(['message' => 'Este token de restablecimiento de contraseña no es válido.'], 422);

            return;
        }

        $expireMinutes = config('auth.password_reset_expire_minutes', 60);

        if (strtotime($record['created_at']) + $expireMinutes * 60 < time()) {
            PasswordResetRepository::deleteByHash($hashedToken);
            Response::json(['message' => 'Este token de restablecimiento de contraseña ha expirado. Por favor, solicita uno nuevo.'], 422);

            return;
        }

        $user = UserRepository::findByEmail($record['email']);

        if (!$user) {
            Response::json(['message' => 'No se encontró usuario para token de restablecimiento.'], 422);

            return;
        }

        UserRepository::updatePassword($user->getId(), $request->input('password'));
        Auth::revokeAllTokens($user->getId());
        PasswordResetRepository::deleteByHash($hashedToken);

        Response::json(['message' => 'Tu contraseña se restableció correctamente. Inicia sesión con tu nueva contraseña.']);
    }
}
