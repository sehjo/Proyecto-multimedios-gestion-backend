<?php

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../dao/authDao.php';
require_once __DIR__ . '/../dao/permissionDao.php';
require_once __DIR__ . '/../dao/userDao.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/env.php';

class AuthController
{
    private AuthDao $dao;

    public function __construct()
    {
        $this->dao = new AuthDao();
    }

    public function login(): void
    {
        $json = getJsonInput();

        $errors = validar($json, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $user = $this->dao->findUserByEmail($json['email']);

        if (!$user || !password_verify($json['password'], $user['password'])) {
            jsonResponse('error', 'Las credenciales proporcionadas son incorrectas.', null, null, null, 401);
        }

        if ($user['status'] === 'inactive') {
            jsonResponse('error', 'Tu cuenta está inactiva. Contacta a un administrador.', null, null, null, 403);
        }

        $token = $this->dao->issueToken($user['id']);

        $permisoDao = new PermissionDao();
        $permissions   = $permisoDao->userAllPermissions($user['id']);

        $usuarioDao = new UserDao();
        $roles      = $usuarioDao->userRoles($user['id']);

        unset($user['password']);

        // Set access token cookie (Plan B)
        setcookie(
            'access_token',
            $token,
            [
                'expires' => time() + 900, // 15 minutes
                'path' => '/',
                'domain' => '',
                'secure' => Env::get('APP_ENV', 'development') === 'production',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        // Keep returning token in the JSON body to maintain compatibility with Postman and API clients
        jsonResponse('success', 'Inicio de sesión exitoso.', [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => 900,
            'user'         => $user,
            'roles'        => $roles,
            'permissions'  => array_column($permissions, 'name'),
        ]);
    }

    public function logout(): void
    {
        $token = getRequestToken();

        if (!$token) {
            jsonResponse('error', 'No autenticado.', null, null, null, 401);
        }

        $user = $this->dao->findUserByToken($token);

        if (!$user) {
            jsonResponse('error', 'Token inválido o expirado.', null, null, null, 401);
        }

        $this->dao->revokeToken($token);

        // Expire cookie
        setcookie(
            'access_token',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => Env::get('APP_ENV', 'development') === 'production',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        jsonResponse('success', 'Sesión cerrada correctamente.');
    }

    public function refresh(): void
    {
        $token = getRequestToken();

        if (!$token) {
            jsonResponse('error', 'No autenticado.', null, null, null, 401);
        }

        $newToken = $this->dao->refreshToken($token);

        if (!$newToken) {
            jsonResponse('error', 'Token inválido o expirado.', null, null, null, 401);
        }

        // Set new access token cookie
        setcookie(
            'access_token',
            $newToken,
            [
                'expires' => time() + 900, // 15 minutes
                'path' => '/',
                'domain' => '',
                'secure' => Env::get('APP_ENV', 'development') === 'production',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        jsonResponse('success', 'Token renovado correctamente.', [
            'access_token' => $newToken,
            'token_type'   => 'bearer',
            'expires_in'   => 900,
        ]);
    }

    public function forgotPassword(): void
    {
        $json    = getJsonInput();
        $errors = validar($json, ['email' => 'required|email']);

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $genericMsg = 'Si este correo está registrado, recibirás un enlace para restablecer tu contraseña en breve.';
        $user  = $this->dao->findUserByEmail($json['email']);

        if (!$user) {
            jsonResponse('success', $genericMsg);
        }

        $plainToken  = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        $this->dao->deleteResetTokenByEmail($user['email']);
        $this->dao->saveResetToken($user['email'], $hashedToken);

        $frontendUrl = Env::get('FRONTEND_URL', 'http://localhost:5173');
        $resetUrl    = $frontendUrl . '/change-password?token=' . $plainToken;
        $expiresAt   = date('d/m/Y H:i', time() + 3600);

        try {
            $mailer = new Mailer();
            $mailer->send(
                $user['email'],
                '[MediCode] Restablece tu contraseña',
                $this->buildResetPasswordTemplate($user['name'], $resetUrl, $expiresAt)
            );
        } catch (\RuntimeException) {
            // Email failed but the token is already saved.
        }

        // Never expose the token in production; the user must obtain it from the
        // email link. Returned only in non-production to support automated tests.
        $data = Env::get('APP_ENV', 'production') !== 'production'
            ? ['reset_token' => $plainToken]
            : null;
        jsonResponse('success', $genericMsg, $data);
    }

    public function resetPassword(): void
    {
        $json    = getJsonInput();
        $errors = validar($json, [
            'token'    => 'required',
            'password' => 'required|min:8|password_strength',
        ]);

        if (!empty($errors)) {
            jsonResponse('error', 'Error de validación.', null, $errors, null, 422);
        }

        $hashedToken = hash('sha256', $json['token']);
        $record    = $this->dao->findResetToken($hashedToken);

        if (!$record) {
            jsonResponse('error', 'El token de restablecimiento no es válido.', null, null, null, 422);
        }

        if (strtotime($record['created_at']) + 3600 < time()) {
            $this->dao->deleteResetTokenByHash($hashedToken);
            jsonResponse('error', 'El token de restablecimiento ha expirado. Solicita uno nuevo.', null, null, null, 422);
        }

        $user = $this->dao->findUserByEmail($record['email']);
        if (!$user) {
            jsonResponse('error', 'No se encontró usuario para este token.', null, null, null, 422);
        }

        $this->dao->updatePassword($user['id'], $json['password']);
        $this->dao->revokeAllTokens($user['id']);
        $this->dao->deleteResetTokenByHash($hashedToken);

        jsonResponse('success', 'Contraseña restablecida correctamente. Inicia sesión con tu nueva contraseña.');
    }

    public function verifyEmail(): void
    {
        $token = $_GET['token'] ?? null;

        if (!$token) {
            jsonResponse('error', 'Token de verificación no proporcionado.', null, null, null, 422);
        }

        $hashedToken = hash('sha256', $token);
        $record    = $this->dao->findResetToken($hashedToken);

        if (!$record) {
            jsonResponse('error', 'Token de verificación inválido o expirado.', null, null, null, 422);
        }

        $this->dao->markEmailVerified($record['email']);
        $this->dao->deleteResetTokenByHash($hashedToken);

        jsonResponse('success', 'Correo verificado correctamente.');
    }

    public function hasPermission(): void
    {
        $user = requireAuth();
        $permission = $_GET['permission'] ?? null;

        if (!$permission) {
            jsonResponse('error', 'Error de validación.', null, [
                'permission' => ['El campo permission es obligatorio.'],
            ], null, 422);
        }

        $permisoDao = new PermissionDao();
        $granted    = $permisoDao->userHasPermission((int) $user['id'], $permission);

        jsonResponse('success', 'Consulta de permiso realizada.', [
            'permission' => $permission,
            'granted'    => $granted,
        ]);
    }

    // -----------------------------------------------------------------------
    // Email templates
    // -----------------------------------------------------------------------

    private function buildResetPasswordTemplate(string $nombre, string $url, string $expira): string
    {
        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>'
             . '<body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0;">'
             . '<div style="max-width:600px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">'
             . '<div style="background:#1a56db;padding:32px;text-align:center;"><h1 style="color:#fff;margin:0;font-size:24px;">MediCode UCR</h1></div>'
             . '<div style="padding:32px;color:#333;">'
             . '<p>Hola, <strong>' . htmlspecialchars($nombre) . '</strong>.</p>'
             . '<p>Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el botón para continuar:</p>'
             . '<div style="text-align:center;margin:32px 0;">'
             . '<a href="' . $url . '" style="background:#1a56db;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;">Restablecer contraseña</a>'
             . '</div>'
             . '<p style="font-size:13px;color:#6b7280;">Este enlace expira el <strong>' . $expira . '</strong>. Si el botón no funciona, copia y pega este enlace:</p>'
             . '<p style="word-break:break-all;font-size:12px;color:#6b7280;">' . $url . '</p>'
             . '<p style="font-size:13px;color:#6b7280;">Si no solicitaste este cambio, puedes ignorar este correo.</p>'
             . '</div>'
             . '<div style="padding:24px 32px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;">'
             . 'MediCode — Sistema de Citas Médicas UCR &bull; Correo automático, no responder.'
             . '</div></div></body></html>';
    }
}
