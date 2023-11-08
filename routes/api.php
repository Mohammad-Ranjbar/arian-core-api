<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockItemController;
use App\Http\Controllers\BlockReasonController;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\LineActivityStartStopController;
use App\Http\Controllers\MakeCodeController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\ProductionLineActivityController;
use App\Http\Controllers\ProductionLineController;
use App\Http\Controllers\ProductionTrackingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\SmsServiceController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\StopFactController;
use App\Http\Controllers\StopReasonController;
use App\Http\Controllers\StopTypeController;
use App\Http\Controllers\TransitActivityController;
use App\Http\Controllers\TransitCompanyController;
use App\Http\Controllers\TransitDestinationController;
use App\Http\Controllers\TransitDriverController;
use App\Http\Controllers\TransitSourceController;
use App\Http\Controllers\TransitStatusLogController;
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
        Route::get('/transit-drivers', [OptionController::class, 'transitDriver']);
        Route::get('/transit-companies', [OptionController::class, 'transitCompany']);
        Route::get('/transit-sources', [OptionController::class, 'transitSource']);
        Route::get('/transit-destinations', [OptionController::class, 'transitDestination']);
        Route::get('/incidents', [OptionController::class, 'incidents']);
        Route::get('/elements', [OptionController::class, 'elements']);

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
        Route::get('/', [
            VehicleFaultController::class, 'listIncidentSource'
        ])->middleware('can:incident-sources.index')->name('incident-sources.index');
        Route::put('/{incidentSource}/update', [
            VehicleFaultController::class, 'updateIncidentSource'
        ])->middleware('can:incident-sources.update')->name('incident-sources.update');
    });
    Route::put('/transit-activities/{transitActivity}/set-priority', [
        TransitActivityController::class, 'setPriority'
    ])->name('transit-activities-set-priority')->can('transit-activities-set-priority');
    Route::post('/transit-activities/{transitActivity}/change-status-temporary-receipt', [
        TransitActivityController::class, 'temporaryReceipt'
    ])->name('transit-activities-set-temporary-receipt')->can('transit-activities-set-temporary-receipt');
    Route::post('/transit-activities/{transitActivity}/change-status-enter', [
        TransitActivityController::class, 'enter'
    ])->name('transit-activities-set-enter')->can('transit-activities-set-enter');
    Route::post('/transit-activities/{transitActivity}/change-status-discharge', [
        TransitActivityController::class, 'discharge'
    ])->name('transit-activities-set-discharge')->can('transit-activities-set-discharge');
    Route::post('/transit-activities/{transitActivity}/create-exit-permit', [
        TransitActivityController::class, 'exitPermit'
    ])->name('transit-activities-set-exit-permit')->can('transit-activities-set-exit-permit');
    Route::post('/transit-activities/{transitActivity}/exit', [
        TransitActivityController::class, 'exit'
    ])->name('transit-activities-set-exit')->can('transit-activities-set-exit');
    Route::post('/transit-activities/{transitActivity}/notify', [
        TransitActivityController::class, 'notify'
    ])->name('transit-activities-notify')->can('transit-activities-notify');
    Route::apiResource('sms-templates', SmsServiceController::class)->only(['index', 'update']);
    Route::apiResource('production-line-activities', ProductionLineActivityController::class)->except('create');
    Route::group([
        'prefix'     => 'production-lines/{production_line}',
        'controller' => LineActivityStartStopController::class,
        'as' => 'production-lines.'
    ], function () {
        Route::get('status', 'status')->name('status');
        Route::post('stop', 'stop')->name('stop');
        Route::post('start', 'start')->name('start');
    });

    Route::post('/production-line-activities/{production_line_activity}/approve',
        [LineActivityStartStopController::class,'approve'])
        ->name('production-line-activities.approve');

    Route::apiResources([
        'roles'                => RoleController::class, 'make-codes' => MakeCodeController::class,
        'car-models'           => CarModelController::class, 'users' => UserController::class,
        'salons'               => SalonController::class, 'production-lines' => ProductionLineController::class,
        'work-group-centers'   => WorkGroupCenterController::class, 'stations' => StationController::class,
        'incidents'            => IncidentController::class, 'elements' => ElementController::class,
        'block-reasons'        => BlockReasonController::class, 'transit-sources' => TransitSourceController::class,
        'transit-destinations' => TransitDestinationController::class,
        'transit-companies'    => TransitCompanyController::class,
        'transit-activities'   => TransitActivityController::class, 'transit-drivers' => TransitDriverController::class,
        'stop-facts'           => StopFactController::class, 'stop-reasons' => StopReasonController::class,
        'stop-types'           => StopTypeController::class,

    ]);
});
