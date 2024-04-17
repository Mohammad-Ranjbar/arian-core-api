<?php

use App\Http\Controllers\AccompanyingItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BasketController;
use App\Http\Controllers\BillOfLadingController;
use App\Http\Controllers\BlockItemController;
use App\Http\Controllers\BlockReasonController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\CustomLocationController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GatePermitController;
use App\Http\Controllers\GatePermitExitController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JoinChannelController;
use App\Http\Controllers\LineActivityStartStopController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MakeCodeController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\ProductionLineActivityController;
use App\Http\Controllers\ProductionLineController;
use App\Http\Controllers\ProductionTrackingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleLocationController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\SmsServiceController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\StopFactController;
use App\Http\Controllers\StopReasonController;
use App\Http\Controllers\StopTypeController;
use App\Http\Controllers\SubmitEngineBarcodeController;
use App\Http\Controllers\SubmitIncidentLocationController;
use App\Http\Controllers\TrackingHistoryController;
use App\Http\Controllers\TransitCompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleFaultController;
use App\Http\Controllers\WorkGroupCenterController;
use Illuminate\Support\Facades\Route;

Route::any('/', function () {
    return response()->json(['version' => app()->version(), 'env' => app()->environment()]);
});

Route::post('auth/login', [AuthController::class, 'login']);
Route::get('auth/check-username', [AuthController::class, 'checkUserName']);
Route::post('/recover-password', [AuthController::class, 'recoverPassword']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::get('/dl', DownloadController::class)->name('dl');
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/custom-locations', CustomLocationController::class)->name('get-custom-locations');
    Route::get('/report-block-items', [BlockItemController::class, 'report'])->name('report-block-items');
    Route::get('/report-vehicle-qc', [VehicleController::class, 'reportVehicleQc'])->name('report-vehicle-qc');
    Route::get('/vehicle-qc', [VehicleController::class, 'vehicleQc'])->name('vehicle-qc');
    Route::get('/report-incidents', [IncidentController::class, 'report'])->name('report-incidents');
    Route::get('/report-production-line-activities', [ProductionLineActivityController::class, 'report'])->name('report-production-line-activities');
    Route::get('/report-barcodes', [BarcodeController::class, 'report'])->name('report-barcodes');
    Route::post('/upload', [FileController::class, 'upload'])->name('upload.store');
    Route::get('/tracking-history/{modelType}/{modelId}/{type}',
        TrackingHistoryController::class)->name('tracking-history');
    Route::get('/get-template-incident', [IncidentController::class, 'getTemplate']);
    Route::get('/get-template-element', [ElementController::class, 'getTemplate']);
    Route::get('/get-template-unblock-items', [BlockItemController::class, 'getTemplateUnblockItems']);

    Route::get('/get-template-bill-of-lading-for-vehicle',
        [BillOfLadingController::class, 'getTemplateBillOfLadingForVehicle']);

    Route::get('/get-template-bill-of-lading-for-container',
        [BillOfLadingController::class, 'getTemplateBillOfLadingForContainer']);

    Route::post('/import-bill-of-lading',
        [BillOfLadingController::class, 'importBillOfLading'])->name('import.bill-of-lading');

    Route::get('/get-stop-locations',
        [SubmitIncidentLocationController::class, 'getStopLocations'])->name('get.stop.locations');

    Route::get('channels', JoinChannelController::class);


    Route::get('/station/{station}/users', [StationController::class, 'users'])->name('station.users');
    Route::post('/station/{station}/users', [StationController::class, 'assignUser'])->name('station.assign.user');
    Route::delete('/station/{station}/users/{user}',
        [StationController::class, 'removeUser'])->name('station.remove.user');

    Route::get('/accompanying-item/{accompanyingItem}/car-models',
        [AccompanyingItemController::class, 'carModels'])->name('accompanying-item.carModels');
    Route::post('/accompanying-item/{accompanyingItem}/car-models',
        [AccompanyingItemController::class, 'assignCarModel'])->name('accompanying-item.assign.carModel');
    Route::delete('/accompanying-item/{accompanyingItem}/car-models/{carModel}',
        [AccompanyingItemController::class, 'removeCarModel'])->name('accompanying-item.remove.carModel');


    Route::get('/sale-location/{saleLocation}/users',
        [SaleLocationController::class, 'users'])->name('saleLocation.users');
    Route::post('/sale-location/{saleLocation}/users',
        [SaleLocationController::class, 'assignUser'])->name('saleLocation.assign.user');
    Route::delete('/sale-location/{saleLocation}/users/{user}',
        [SaleLocationController::class, 'removeUser'])->name('saleLocation.remove.user');


    Route::get('/location/{location}/users',
        [LocationController::class, 'users'])->name('location.users');
    Route::post('/location/{location}/users',
        [LocationController::class, 'assignUser'])->name('location.assign.user');
    Route::delete('/location/{location}/users/{user}',
        [LocationController::class, 'removeUser'])->name('location.remove.user');


    Route::get('/production-line/{productionLine}/users',
        [ProductionLineController::class, 'users'])->name('production-line.users');
    Route::post('/production-line/{productionLine}/users',
        [ProductionLineController::class, 'assignUsers'])->name('production-line.assign.user');
    Route::delete('/production-line/{productionLine}/users/{user}',
        [ProductionLineController::class, 'removeUser'])->name('production-line.remove.user');


    Route::get('/job-tracking/{job}', JobController::class)->name('job.tracking');
    Route::post('/incidents-imports', [IncidentController::class, 'import'])->name('incidents.imports');
    Route::post('/elements-imports', [ElementController::class, 'import'])->name('elements.imports');
    Route::post('/unblock-items-imports',
        [BlockItemController::class, 'importUnblockItems'])->name('unblock-items.imports');
    Route::post('/block-items-imports', [BlockItemController::class, 'importBlockItems'])->name('block-items.imports');
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/init', [AuthController::class, 'init']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    Route::group(['prefix' => '/bill-of-lading/{bill_of_lading}'], function () {
        Route::put('/change-status/{status}', [BillOfLadingController::class, 'changeStatus']);
        Route::put('/notice', [BillOfLadingController::class, 'notice'])->name('bill-of-lading.notice');
        Route::put('/set-priority',
            [BillOfLadingController::class, 'setPriority'])->name('bill-of-lading.set-priority');
    });

    Route::get('options/{resource}', OptionController::class)->middleware(['CheckAccessUser']);
    Route::middleware('access.vehicle')->group(function () {
        Route::prefix('vehicles/{vehicle}')->group(function () {

            Route::post('engine-barcode', SubmitEngineBarcodeController::class)->name('submit.engine.barcode');

            Route::post('/gate-permit-exit',
                GatePermitExitController::class)->withoutMiddleware('access.vehicle')->name('gate-permit-exit');
            Route::get('/get-available-sale-locations',
                [SaleLocationController::class, 'getAvailableSaleLocations'])->name('get-available-sale-locations');
            Route::post('/set-sale-location',
                [SaleLocationController::class, 'setSaleLocation'])->name('set-sale-location');
            Route::apiResource('/inspections', InspectionController::class)->only(['store', 'index', 'show']);
            Route::get('/block-items', [BlockItemController::class, 'index'])->name('block-items.index');
            Route::post('/block-items', [BlockItemController::class, 'store'])->name('block-items.store');
            Route::put('/block-items/{id}/unblock',
                [BlockItemController::class, 'unblock'])->name('block-items.unblock');
            Route::get('/', [VehicleController::class, 'getByVin'])->withoutMiddleware('access.vehicle');
            Route::get('/faults/{type}', [VehicleFaultController::class, 'index']);
            Route::post('/faults/{type}', [VehicleFaultController::class, 'submit']);
            Route::delete('/faults/{id}', [VehicleFaultController::class, 'destroy'])->name('destroy.vehicle.fault');
            Route::put('/update-qc-status',
                [VehicleController::class, 'updateQcStatus'])->name('vehicle.update-qc-status');
            Route::put('/update-grade-status',
                [VehicleController::class, 'updateGradeStatus'])->name('vehicle.update-grade-status');
        });
        Route::post('/production-tracking/{vehicle}', [ProductionTrackingController::class, 'submit']);
    });
    Route::put('/update-production-tracking/{tracking}',
        [ProductionTrackingController::class, 'update'])->name('update-production-tracking');
    Route::get('/faults/{type}/report', [VehicleFaultController::class, 'report']);
    Route::get('/faults/{type}/report-excel', [VehicleFaultController::class, 'reportExcel']);

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

    Route::apiResource('sms-templates', SmsServiceController::class)->only(['index', 'update', 'show']);
    Route::apiResource('production-line-activities', ProductionLineActivityController::class)->except('store');
    Route::apiResource('bill-of-ladings', BillOfLadingController::class)->except('update');
    Route::group([
        'prefix' => 'production-lines/{production_line}',
        'controller' => LineActivityStartStopController::class, 'as' => 'production-lines.'
    ], function () {
        Route::get('status', 'status')->name('status');
        Route::post('stop', 'stop')->name('stop');
        Route::post('start', 'start')->name('start');
        Route::post('approve', 'approve')->name('approve');
    });

    Route::post('/production-line-activities/{production_line_activity}/approve',
        [LineActivityStartStopController::class, 'approve'])->name('production-line-activities.approve');

    Route::apiResource('gate-permits', GatePermitController::class)->only(['index', 'store', 'destroy']);


    Route::apiResources([
        'roles' => RoleController::class,
        'make-codes' => MakeCodeController::class,
        'car-models' => CarModelController::class,
        'users' => UserController::class,
        'salons' => SalonController::class,
        'production-lines' => ProductionLineController::class,
        'work-group-centers' => WorkGroupCenterController::class,
        'stations' => StationController::class,
        'incidents' => IncidentController::class,
        'elements' => ElementController::class,
        'block-reasons' => BlockReasonController::class,
        'transit-companies' => TransitCompanyController::class,
        'cars' => CarController::class,
        'stop-facts' => StopFactController::class,
        'stop-reasons' => StopReasonController::class,
        'stop-types' => StopTypeController::class,
        'barcodes' => BarcodeController::class,
        'baskets' => BasketController::class,
        'inventories' => InventoryController::class,
        'containers' => ContainerController::class,
        'drivers' => DriverController::class,
        'sale-locations' => SaleLocationController::class,
        'accompanying-items' => AccompanyingItemController::class,
        'locations' => LocationController::class,
    ]);
});
