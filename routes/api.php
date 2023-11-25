<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BasketController;
use App\Http\Controllers\BillOfLadingController;
use App\Http\Controllers\BlockItemController;
use App\Http\Controllers\BlockReasonController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\GatePermitController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\InventoryController;
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
use App\Http\Controllers\TransitCompanyController;
use App\Http\Controllers\TransitDestinationController;
use App\Http\Controllers\TransitSourceController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleFaultController;
use App\Http\Controllers\WorkGroupCenterController;
use Illuminate\Support\Facades\Route;


Route::post('/upload', UploadController::class);
Route::post('auth/login', [AuthController::class, 'login']);
Route::get('auth/check-username', [AuthController::class, 'checkUserName']);
Route::post('/recover-password', [AuthController::class, 'recoverPassword']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/init', [AuthController::class, 'init']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    Route::group(['prefix' => '/bill-of-lading/{bill_of_lading}'], function () {
        Route::put('/change-status/{status}', [BillOfLadingController::class, 'changeStatus']);
        Route::get('/notice', [BillOfLadingController::class, 'notice']);
        Route::put('/set-priority',
            [BillOfLadingController::class, 'setPriority'])->name('bill-of-lading.set-priority');
    });

    Route::get('options/{resource}', OptionController::class);
    Route::middleware('access.vehicle')->group(function () {
        Route::prefix('vehicles/{vehicle}')->group(function () {
            Route::get('/block-items', [BlockItemController::class, 'index'])->name('block-items.index');
            Route::post('/block-items', [BlockItemController::class, 'store'])->name('block-items.store');
            Route::put('/block-items/{id}/unblock',
                [BlockItemController::class, 'unblock'])->name('block-items.unblock');
            Route::get('/', [VehicleController::class, 'getByVin'])->withoutMiddleware('access.vehicle');
            Route::get('/faults/{type}', [VehicleFaultController::class, 'index']);
            Route::post('/faults/{type}', [VehicleFaultController::class, 'submit']);
            Route::put('/update-qc-status',
                [VehicleController::class, 'updateQcStatus'])->name('vehicle.update-qc-status');
            Route::put('/update-grade-status',
                [VehicleController::class, 'updateGradeStatus'])->name('vehicle.update-grade-status');
        });
        Route::post('/production-tracking/{vehicle}', [ProductionTrackingController::class, 'submit']);
    });

    Route::prefix('/incident-sources')->group(function () {
        Route::get('/', [
            VehicleFaultController::class, 'listIncidentSource'
        ])->middleware('can:incident-sources.index')->name('incident-sources.index');
        Route::put('/{incidentSource}', [
            VehicleFaultController::class, 'updateIncidentSource'
        ])->middleware('can:incident-sources.update')->name('incident-sources.update');
        Route::get('/{incidentSource}', [
            VehicleFaultController::class, 'showIncidentSource'
        ])->middleware('can:incident-sources.show')->name('incident-sources.show');
    });

    Route::apiResource('sms-templates', SmsServiceController::class)->only(['index', 'update']);
    Route::apiResource('production-line-activities', ProductionLineActivityController::class)->except('store');
    Route::apiResource('bill-of-ladings', BillOfLadingController::class)->except('update');
    Route::group([
        'prefix' => 'production-lines/{production_line}', 'controller' => LineActivityStartStopController::class,
        'as'     => 'production-lines.'
    ], function () {
        Route::get('status', 'status')->name('status');
        Route::post('stop', 'stop')->name('stop');
        Route::post('start', 'start')->name('start');
    });

    Route::post('/production-line-activities/{production_line_activity}/approve',
        [LineActivityStartStopController::class, 'approve'])->name('production-line-activities.approve');
    Route::apiResources([
        'roles'                => RoleController::class, 'make-codes' => MakeCodeController::class,
        'car-models'           => CarModelController::class, 'users' => UserController::class,
        'salons'               => SalonController::class, 'production-lines' => ProductionLineController::class,
        'work-group-centers'   => WorkGroupCenterController::class, 'stations' => StationController::class,
        'incidents'            => IncidentController::class, 'elements' => ElementController::class,
        'block-reasons'        => BlockReasonController::class, 'transit-sources' => TransitSourceController::class,
        'transit-destinations' => TransitDestinationController::class,
        'transit-companies'    => TransitCompanyController::class, 'cars' => CarController::class,
        'stop-facts'           => StopFactController::class, 'stop-reasons' => StopReasonController::class,
        'stop-types'           => StopTypeController::class, 'barcodes' => BarcodeController::class,
        'baskets'              => BasketController::class, 'inventories' => InventoryController::class,
        'containers'           => ContainerController::class, 'gate-permits' => GatePermitController::class,
        'drivers'              => DriverController::class
    ]);
});
