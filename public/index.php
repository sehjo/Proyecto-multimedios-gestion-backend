<?php

require_once __DIR__ . '/../core/bootstrap.php';

$request = new Request();
$router = new Router();

// Health check fuera del prefijo /api, equivalente al `health: '/up'` del bootstrap/app.php original.
$router->get('/up', [HealthController::class, 'index']);

$router->get('/api/user', [AuthController::class, 'me']);
$router->post('/api/login', [AuthController::class, 'login']);
$router->post('/api/logout', [AuthController::class, 'logout']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

$router->get('/api/dashboard', [DashboardController::class, 'index']);

$router->resource('api/users', UserController::class);
$router->resource('api/user-types', UsersTypeController::class);
$router->resource('api/patients', PatientController::class);

$router->dispatch($request);
