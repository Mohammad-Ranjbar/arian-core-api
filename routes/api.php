<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',[AuthController::class,'logout']);
    Route::get('/check-user',[AuthController::class,'checkUser']);
    Route::put('/change-password',[AuthController::class,'changePassword']);

    Route::apiResources([
        'roles' => RoleController::class
    ]);
});
