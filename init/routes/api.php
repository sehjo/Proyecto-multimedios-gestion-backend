<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PriorityController;
use App\Http\Controllers\Api\DrugController;
use App\Http\Controllers\Api\DiseaseController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\DiagnosisController;
use App\Http\Controllers\Api\DiagnosesHasTreatmentController;
use App\Http\Controllers\Api\DiseaseHasTreatmentController;

/*
|--------------------------------------------------------------------------
| Rutas públicas (sin autenticación) — HU-003, HU-004
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas — requieren sesión activa (Sanctum). HU-004
|--------------------------------------------------------------------------
| Todo lo interno vive dentro de este grupo. Sin token válido → 401.
| La autorización fina por rol/permiso (Spatie) se aplica por acción.
*/
/**
 * Registra un CRUD de API aplicando UN permiso por acción según la convención
 * "<permission>.<view|create|update|delete>". Cada verbo HTTP queda atado a su
 * permiso correcto (a diferencia de apiResource()->middleware([...]), que aplica
 * todos los middleware a todas las rutas).
 *
 * Es un closure (no una función global) para que el archivo de rutas pueda
 * recargarse varias veces —p. ej. en los tests— sin "Cannot redeclare".
 */
$registerCrud = function (string $uri, string $controller, string $permission): void {
    $param = Str::singular(str_replace('-', '_', $uri));

    Route::get($uri, [$controller, 'index'])
        ->middleware("permission:{$permission}.view")->name("{$uri}.index");

    Route::get("{$uri}/{{$param}}", [$controller, 'show'])
        ->middleware("permission:{$permission}.view")->name("{$uri}.show");

    Route::post($uri, [$controller, 'store'])
        ->middleware("permission:{$permission}.create")->name("{$uri}.store");

    Route::match(['put', 'patch'], "{$uri}/{{$param}}", [$controller, 'update'])
        ->middleware("permission:{$permission}.update")->name("{$uri}.update");

    Route::delete("{$uri}/{{$param}}", [$controller, 'destroy'])
        ->middleware("permission:{$permission}.delete")->name("{$uri}.destroy");
};

Route::middleware('auth:sanctum')->group(function () use ($registerCrud) {

    // Usuario autenticado actual y cierre de sesión (HU-002).
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |----------------------------------------------------------------------
    | Gestión de usuarios — solo Administrador
    |----------------------------------------------------------------------
    */
    Route::apiResource('users', UserController::class)
        ->middleware('role:Administrador');

    // Asignar/cambiar el rol de un usuario (solo roles, nunca permisos directos).
    Route::put('users/{user}/role', [RoleController::class, 'assignToUser'])
        ->middleware('role:Administrador')->whereNumber('user');

    /*
    |----------------------------------------------------------------------
    | Gestión de roles y permisos — solo Administrador
    | Rutas estáticas antes que las dinámicas {role}.
    |----------------------------------------------------------------------
    */
    Route::middleware('role:Administrador')->group(function () {
        Route::get('roles/permissions', [RoleController::class, 'permissions']);
        Route::apiResource('roles', RoleController::class);
    });

    /*
    |----------------------------------------------------------------------
    | Recursos con permiso fino por acción (view/create/update/delete).
    | Se define una ruta por método para que cada verbo exija UN permiso.
    |----------------------------------------------------------------------
    */
    $registerCrud('priorities', PriorityController::class, 'priorities');
    $registerCrud('drugs', DrugController::class, 'drugs');
    $registerCrud('diseases', DiseaseController::class, 'diseases');
    $registerCrud('patients', PatientController::class, 'patients');
    $registerCrud('diagnoses', DiagnosisController::class, 'diagnoses');
    $registerCrud('diagnoses-has-treatments', DiagnosesHasTreatmentController::class, 'treatments');
    $registerCrud('disease-has-treatments', DiseaseHasTreatmentController::class, 'treatments');
});
