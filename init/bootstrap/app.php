<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware de autorización de Spatie (roles y permisos).
        $middleware->alias([
            'role'               => RoleMiddleware::class,
            'permission'         => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 422 — Errores de validación (formato existente del proyecto).
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación: Algunos campos exceden el límite permitido o son inválidos.',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => collect($e->errors())->map(function ($messages) {
                        return array_map(function ($msg) {
                            return str_replace('The ', 'El campo ', $msg); // Small hack or use lang files
                        }, $messages);
                    })->toArray(),
                ], 422); // 422 Unprocessable Entity es el código HTTP correspondiente
            }
        });

        // 401 — No autenticado (sin token, token inválido o expirado). HU-004.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'No autenticado. Inicia sesión para continuar.',
                    'error_code' => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        // 403 — Autenticado pero sin el rol/permiso requerido (Spatie). HU-004.
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'No tienes permisos para realizar esta acción.',
                    'error_code' => 'FORBIDDEN',
                ], 403);
            }
        });
    })->create();
