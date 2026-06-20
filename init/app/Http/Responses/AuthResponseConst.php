<?php

namespace App\Http\Responses;

/**
 * Example responses for the authentication endpoints. The example payloads
 * mirror exactly what AuthController returns.
 */
class AuthResponseConst
{
    public const LOGIN_SUCCESS = [
        'status'      => 200,
        'description' => 'Login successful. Returns a Bearer token and the user.',
        'examples'    => [
            'token' => '12|2o0IE9JiQMFsvA9p4QaL5pj6oUbiyPSxMgZ54igq72f73f91e',
            'user'  => [
                'id'          => 1,
                'name'        => 'Carlos',
                'lastname'    => 'Ramírez',
                'email'       => 'admin@ccss.cr',
                'roles'       => ['Administrador'],
                'permissions' => ['users.read', 'users.create', 'roles.read'],
                'created_at'  => '2026-06-12T15:00:00.000000Z',
                'updated_at'  => '2026-06-12T15:00:00.000000Z',
            ],
        ],
    ];

    public const LOGIN_INVALID = [
        'status'      => 422,
        'description' => 'Invalid credentials.',
        'examples'    => [
            'success'    => false,
            'message'    => 'Error de validación: Algunos campos exceden el límite permitido o son inválidos.',
            'error_code' => 'VALIDATION_ERROR',
            'errors'     => [
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ],
        ],
    ];

    public const LOGOUT_SUCCESS = [
        'status'      => 200,
        'description' => 'Session closed. The current token is revoked.',
        'examples'    => [
            'message' => 'Sesión cerrada correctamente.',
        ],
    ];

    public const FORGOT_PASSWORD_SUCCESS = [
        'status'      => 200,
        'description' => 'Generic response (sent whether or not the email exists).',
        'examples'    => [
            'message' => 'Si este correo está registrado, recibirás un enlace para restablecer tu contraseña en breve.',
        ],
    ];

    public const RESET_PASSWORD_SUCCESS = [
        'status'      => 200,
        'description' => 'Password reset successfully.',
        'examples'    => [
            'message' => 'Tu contraseña se restableció correctamente. Inicia sesión con tu nueva contraseña.',
        ],
    ];

    public const RESET_PASSWORD_INVALID = [
        'status'      => 422,
        'description' => 'The reset token is invalid or has expired.',
        'examples'    => [
            'message' => 'Este token de restablecimiento de contraseña no es válido.',
        ],
    ];

    public const HAS_PERMISSION_SUCCESS = [
        'status'      => 200,
        'description' => 'Whether the authenticated user holds the given permission.',
        'examples'    => [
            'permission' => 'patients.read',
            'granted'    => true,
        ],
    ];
}
