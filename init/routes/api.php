<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\LogUserController;
use App\Http\Controllers\Api\LogRoleController;
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
        ->middleware("permission:{$permission}.read")->name("{$uri}.index");

    Route::get("{$uri}/{{$param}}", [$controller, 'show'])
        ->middleware("permission:{$permission}.read")->name("{$uri}.show");

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

    // Permission check: lets the front ask whether the user has a permission.
    Route::get('auth/has-permission', [AuthController::class, 'hasPermission']);

    /*
    |----------------------------------------------------------------------
    | User management — fine-grained permissions (users.*)
    | Static routes before the resource to avoid wildcard conflicts.
    | One route per verb so each action requires exactly ONE permission
    | (apiResource()->middleware([...]) would apply them all to every route).
    |----------------------------------------------------------------------
    */
    Route::get('stats/users/by-role', [UserController::class, 'statsByRole'])
        ->middleware('permission:users.read');
    Route::get('stats/users/by-status', [UserController::class, 'statsByStatus'])
        ->middleware('permission:users.read');

    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:users.read')->name('users.index');
    Route::get('users/{user}', [UserController::class, 'show'])
        ->middleware('permission:users.read')->whereNumber('user')->name('users.show');
    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:users.create')->name('users.store');
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.update')->whereNumber('user')->name('users.update');

    // Activate / deactivate a user (status change). Both directions share the
    // same route; fine-grained permission (users.delete to deactivate,
    // users.update to reactivate) is enforced inside the controller action.
    Route::patch('users/{user}/status', [UserController::class, 'changeStatus'])
        ->whereNumber('user')->name('users.status');

    // Manage a user's roles (a user can hold several roles).
    // PUT replaces the whole set atomically (for "edit all roles" UIs); POST/DELETE
    // add or remove one role at a time.
    Route::put('users/{user}/roles', [RoleController::class, 'syncUserRoles'])
        ->middleware('permission:users.update')->whereNumber('user');
    Route::post('users/{user}/roles', [RoleController::class, 'addRoleToUser'])
        ->middleware('permission:users.update')->whereNumber('user');
    Route::delete('users/{user}/roles/{role}', [RoleController::class, 'removeRoleFromUser'])
        ->middleware('permission:users.update')->whereNumber(['user', 'role']);

    /*
    |----------------------------------------------------------------------
    | Role and permission management — fine-grained permissions (roles.*)
    | Static routes before the dynamic {role} ones.
    |----------------------------------------------------------------------
    */
    Route::get('roles/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:roles.read');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.read')->name('roles.index');
    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->middleware('permission:roles.read')->whereNumber('role')->name('roles.show');
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create')->name('roles.store');
    Route::match(['put', 'patch'], 'roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.update')->whereNumber('role')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')->whereNumber('role')->name('roles.destroy');

    /*
    |----------------------------------------------------------------------
    | Audit logs (read-only)
    |----------------------------------------------------------------------
    */
    Route::get('logs/users', [LogUserController::class, 'index'])
        ->middleware('permission:logs_users.read')->name('logs.users');
    Route::get('logs/roles', [LogRoleController::class, 'index'])
        ->middleware('permission:logs_roles.read')->name('logs.roles');

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
