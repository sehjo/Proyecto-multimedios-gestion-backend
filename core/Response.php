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

    /**
     * Consistent error envelope: { success:false, message, error_code }.
     * Mirrors the helpers of the Laravel UserController (selfActionError, etc.).
     */
    public static function error(string $message, string $errorCode, int $status): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ], $status);
    }

    /** 401 — no authenticated session. */
    public static function unauthenticated(string $message = 'No autenticado.'): void
    {
        self::error($message, 'UNAUTHENTICATED', 401);
    }

    /** 403 — authenticated but lacks the required permission. */
    public static function forbidden(string $message = 'No tienes permisos para realizar esta acción.'): void
    {
        self::error($message, 'FORBIDDEN', 403);
    }
}
