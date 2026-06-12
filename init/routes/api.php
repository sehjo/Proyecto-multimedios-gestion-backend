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
| Public routes (no authentication) — HU-003, HU-004
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Protected routes — require an active session (Sanctum). HU-004
|--------------------------------------------------------------------------
| Everything internal lives inside this group. Without a valid token → 401.
| Fine-grained role/permission authorization (Spatie) is applied per action.
*/
/**
 * Registers an API CRUD applying ONE permission per action following the
 * "<permission>.<view|create|update|delete>" convention. Each HTTP verb is
 * bound to its correct permission (unlike apiResource()->middleware([...]),
 * which applies every middleware to every route).
 *
 * It is a closure (not a global function) so the routes file can be reloaded
 * several times —e.g. in the tests— without a "Cannot redeclare" error.
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

    // Current authenticated user and logout (HU-002).
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |----------------------------------------------------------------------
    | User management — Administrador only
    |----------------------------------------------------------------------
    */
    Route::apiResource('users', UserController::class)
        ->middleware('role:Administrador');

    // Assign/change a user's role (roles only, never direct permissions).
    Route::put('users/{user}/role', [RoleController::class, 'assignToUser'])
        ->middleware('role:Administrador')->whereNumber('user');

    /*
    |----------------------------------------------------------------------
    | Role and permission management — Administrador only
    | Static routes before the dynamic {role} ones.
    |----------------------------------------------------------------------
    */
    Route::middleware('role:Administrador')->group(function () {
        Route::get('roles/permissions', [RoleController::class, 'permissions']);
        Route::apiResource('roles', RoleController::class);
    });

    /*
    |----------------------------------------------------------------------
    | Resources with fine-grained per-action permission (view/create/update/delete).
    | One route per method is defined so each verb requires ONE permission.
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
