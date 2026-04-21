<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UsersTypeController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\DashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/dashboard', [DashboardController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/
Route::apiResource('users', UserController::class);
Route::apiResource('user-types', UsersTypeController::class);

/*
|--------------------------------------------------------------------------
| Patients
|--------------------------------------------------------------------------
*/
Route::apiResource('patients', PatientController::class);
