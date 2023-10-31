<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\MakeCodeController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/init', [AuthController::class, 'checkUser']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/recover-password', [AuthController::class, 'recoverPassword']);
        Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    });
    Route::prefix('options')->group(function () {
        Route::get('/permissions', [OptionController::class, 'permissions']);
        Route::get('/roles', [OptionController::class, 'roles']);
    });

    Route::apiResources([
        'roles'      => RoleController::class, 'make-codes' => MakeCodeController::class,
        'car-models' => CarModelController::class
    ]);
});
