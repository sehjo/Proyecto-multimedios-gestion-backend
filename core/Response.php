<?php

class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function noContent(): void
    {
        http_response_code(204);
    }

    /**
     * Misma forma de respuesta que el manejador de ValidationException del backend
     * Laravel original (bootstrap/app.php): success/message/error_code/errors.
     */
    public static function validationError(array $errors): void
    {
        self::json([
            'success' => false,
            'message' => 'Error de validación: Algunos campos exceden el límite permitido o son inválidos.',
            'error_code' => 'VALIDATION_ERROR',
            'errors' => $errors,
        ], 422);
    }
}
