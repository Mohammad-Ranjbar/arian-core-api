<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockItemController;
use App\Http\Controllers\BlockReasonController;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\MakeCodeController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\ProductionLineController;
use App\Http\Controllers\ProductionTrackingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleFaultController;
use App\Http\Controllers\WorkGroupCenterController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);
Route::get('auth/check-username', [AuthController::class, 'checkUserName']);
Route::middleware('auth:sanctum')->group(function () {
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
        Route::get('/salons', [OptionController::class, 'salons']);
        Route::get('/production-lines', [OptionController::class, 'productionLines']);
        Route::get('/work-group-centers', [OptionController::class, 'workGroupCenters']);
        Route::get('/stations', [OptionController::class, 'stations']);
        Route::get('/make-codes', [OptionController::class, 'makeCodes']);

    });

    Route::middleware('access.vehicle')->group(function () {
        Route::prefix('vehicles/{vehicle}')->group(function () {
            Route::get('/block-items',
                [BlockItemController::class, 'index'])->name('block-items-index')->can('block-items-index');
            Route::post('/block-items',
                [BlockItemController::class, 'store'])->name('block-items-store')->can('block-items-store');
            Route::put('/block-items/{id}/unblock',
                [BlockItemController::class, 'unblock'])->name('block-items-unblock')->can('block-items-unblock');
            Route::get('/', [VehicleController::class, 'getByVin'])->withoutMiddleware('access.vehicle');
            Route::get('/faults/{type}', [VehicleFaultController::class, 'index']);
            Route::post('/faults/{type}', [VehicleFaultController::class, 'submit']);
        });
        Route::post('/production-tracking/{vehicle}', [ProductionTrackingController::class, 'submit']);
    });

    Route::prefix('/incident-sources')->group(function () {
        Route::put('/{incidentSource}/update', [VehicleFaultController::class, 'updateIncidentSource']);
        Route::get('/list', [VehicleFaultController::class, 'listIncidentSource']);
    });


    Route::apiResources([
        'roles'              => RoleController::class,
        'make-codes'         => MakeCodeController::class,
        'car-models'         => CarModelController::class,
        'users'              => UserController::class,
        'salons'             => SalonController::class,
        'production-lines'   => ProductionLineController::class,
        'work-group-centers' => WorkGroupCenterController::class,
        'stations'           => StationController::class,
        'incidents'          => IncidentController::class,
        'elements'           => ElementController::class,
        'block-reasons'      => BlockReasonController::class,
    ]);
});
