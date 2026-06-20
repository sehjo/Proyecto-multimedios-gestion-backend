<?php

namespace App\Http\Responses;

/**
 * Shared example responses for the common error codes returned across the API.
 * The examples match exactly what bootstrap/app.php returns for each case so
 * the docs reflect the real payloads.
 */
class GlobalResponseConst
{
    public const UNAUTHENTICATED = [
        'status'      => 401,
        'description' => 'Not authenticated (missing, invalid or expired token).',
        'examples'    => [
            'success'    => false,
            'message'    => 'No autenticado. Inicia sesión para continuar.',
            'error_code' => 'UNAUTHENTICATED',
        ],
    ];

    public const FORBIDDEN = [
        'status'      => 403,
        'description' => 'Authenticated but missing the required role/permission.',
        'examples'    => [
            'success'    => false,
            'message'    => 'No tienes permisos para realizar esta acción.',
            'error_code' => 'FORBIDDEN',
        ],
    ];

    public const VALIDATION_ERROR = [
        'status'      => 422,
        'description' => 'Validation error. One or more fields are invalid.',
        'examples'    => [
            'success'    => false,
            'message'    => 'Error de validación: Algunos campos exceden el límite permitido o son inválidos.',
            'error_code' => 'VALIDATION_ERROR',
            'errors'     => [
                'name' => ['El nombre del rol es obligatorio.'],
            ],
        ],
    ];
}
