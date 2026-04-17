<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UsersTypeController;
use App\Http\Controllers\Api\PriorityController;
use App\Http\Controllers\Api\DrugController;
use App\Http\Controllers\Api\DiseaseController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\DiagnosisController;
use App\Http\Controllers\Api\DiagnosesHasTreatmentController;
use App\Http\Controllers\Api\DiseaseHasTreatmentController;

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
| User & Auth
|--------------------------------------------------------------------------
*/
Route::apiResource('users', UserController::class);
Route::apiResource('user-types', UsersTypeController::class);

/*
|--------------------------------------------------------------------------
| Catalogs
|--------------------------------------------------------------------------
*/
Route::apiResource('priorities', PriorityController::class);
Route::apiResource('drugs', DrugController::class);

/*
|--------------------------------------------------------------------------
| Clinical entities
|--------------------------------------------------------------------------
*/
Route::apiResource('diseases', DiseaseController::class);
Route::apiResource('patients', PatientController::class);
Route::apiResource('diagnoses', DiagnosisController::class);

/*
|--------------------------------------------------------------------------
| Treatments
|--------------------------------------------------------------------------
*/
Route::apiResource('diagnoses-has-treatments', DiagnosesHasTreatmentController::class);
Route::apiResource('disease-has-treatments', DiseaseHasTreatmentController::class);
