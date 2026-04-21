<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
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
    })->create();
