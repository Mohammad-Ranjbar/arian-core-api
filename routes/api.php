<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\MakeCodeController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);
Route::get('auth/check-username', [AuthController::class, 'checkUserName']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/vehicles/{vin}', [VehicleController::class, 'getByVin']);

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/init', [AuthController::class, 'init']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/recover-password', [AuthController::class, 'recoverPassword']);
        Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    });
    Route::prefix('options')->group(function () {
        Route::get('/permissions', [OptionController::class, 'permissions']);
        Route::get('/roles', [OptionController::class, 'roles']);
    });

    Route::apiResources([
        'roles'      => RoleController::class, 'make_codes' => MakeCodeController::class,
        'car_models' => CarModelController::class, 'users' => UserController::class,
        'salons'     => SalonController::class
    ]);
});
