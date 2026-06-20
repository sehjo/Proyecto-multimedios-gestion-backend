<?php

require_once __DIR__ . '/../core/bootstrap.php';

$request = new Request();

// CORS: emit headers and short-circuit preflight (OPTIONS) before routing.
Cors::handle($request);

$router = new Router();

// Health check fuera del prefijo /api, equivalente al `health: '/up'` del bootstrap/app.php original.
$router->get('/up', [HealthController::class, 'index']);

$router->get('/api/user', [AuthController::class, 'me']);
$router->post('/api/login', [AuthController::class, 'login']);
$router->post('/api/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/has-permission', [AuthController::class, 'hasPermission']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

$router->get('/api/dashboard', [DashboardController::class, 'index']);

// --- User stats & status (static/specific routes BEFORE the {id} resource) ---
$router->get('/api/stats/users/by-role', [UserController::class, 'statsByRole']);
$router->get('/api/stats/users/by-status', [UserController::class, 'statsByStatus']);
$router->put('/api/users/{userId}/role', [RoleController::class, 'assignToUser']);
$router->put('/api/users/{userId}/roles', [RoleController::class, 'syncUserRoles']);
$router->put('/api/users/{id}/status', [UserController::class, 'changeStatus']);

$router->resource('api/users', UserController::class);

// --- Roles & permissions (permissions route BEFORE the {id} resource) ---
$router->get('/api/roles/permissions', [RoleController::class, 'permissions']);
$router->resource('api/roles', RoleController::class);

// --- Audit logs (read-only) ---
$router->get('/api/logs/users', [LogUserController::class, 'index']);
$router->get('/api/logs/roles', [LogRoleController::class, 'index']);

$router->resource('api/user-types', UsersTypeController::class);
$router->resource('api/patients', PatientController::class);

$router->dispatch($request);
