<?php

use App\Http\Controllers\AccompanyingItemController;
use App\Http\Controllers\ActionLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BasketController;
use App\Http\Controllers\BillOfLadingController;
use App\Http\Controllers\BlockHistoryController;
use App\Http\Controllers\BlockItemController;
use App\Http\Controllers\BlockReasonController;
use App\Http\Controllers\BodyOptionController;
use App\Http\Controllers\CabinNumberController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CarModelColorCodeController;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\ChangeVinController;
use App\Http\Controllers\ColorMapController;
use App\Http\Controllers\ComponentItemController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\CopPlanController;
use App\Http\Controllers\CustomLocationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliverVehicleToCustomerController;
use App\Http\Controllers\DisplayBoardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\EnterEngineController;
use App\Http\Controllers\ErpJobController;
use App\Http\Controllers\ErpProductItemController;
use App\Http\Controllers\ExportBomController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GatePermitController;
use App\Http\Controllers\GatePermitTransferController;
use App\Http\Controllers\GeneralProductItemController;
use App\Http\Controllers\GetQcStatusActionLogController;
use App\Http\Controllers\IncidentCategoryController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncomingVehicleController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemInputChecklistController;
use App\Http\Controllers\ItemTechnicalChecklistController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JoinChannelController;
use App\Http\Controllers\LineActivityStartStopController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationVehicleController;
use App\Http\Controllers\MakeCodeController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\PalletController;
use App\Http\Controllers\PalletTransactionController;
use App\Http\Controllers\PalletTypeController;
use App\Http\Controllers\PdiCheckListController;
use App\Http\Controllers\PendingInvoiceController;
use App\Http\Controllers\ProductBomController;
use App\Http\Controllers\ProductionChecklistController;
use App\Http\Controllers\ProductionControlController;
use App\Http\Controllers\ProductionLineActivityController;
use App\Http\Controllers\ProductionLineController;
use App\Http\Controllers\ProductionTestController;
use App\Http\Controllers\ProductionTestResultController;
use App\Http\Controllers\ProductionTrackingController;
use App\Http\Controllers\QcCodeController;
use App\Http\Controllers\ReceiptVehicleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleCompanyController;
use App\Http\Controllers\SaleInvoicesController;
use App\Http\Controllers\SaleLocationController;
use App\Http\Controllers\SalesBridgeController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\ScrewController;
use App\Http\Controllers\SendSmsGatewayController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShortageController;
use App\Http\Controllers\SmsServiceController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\StopFactController;
use App\Http\Controllers\StopReasonController;
use App\Http\Controllers\StopTypeController;
use App\Http\Controllers\SubmitEngineBarcodeController;
use App\Http\Controllers\SubmitIncidentLocationController;
use App\Http\Controllers\SynchronousController;
use App\Http\Controllers\SynchronousTypeController;
use App\Http\Controllers\SynchronousTypeItemController;
use App\Http\Controllers\TorqueCoefficientController;
use App\Http\Controllers\TorqueMeasuringEquipmentController;
use App\Http\Controllers\TorqueMeterController;
use App\Http\Controllers\TorqueTestGroupController;
use App\Http\Controllers\TrackingHistoryController;
use App\Http\Controllers\TransitCompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleFaultController;
use App\Http\Controllers\VehicleOverviewController;
use App\Http\Controllers\VehicleTorqueTestController;
use App\Http\Controllers\VinEngineController;
use App\Http\Controllers\WorkGroupCenterController;
use App\Http\Controllers\YearMapController;
use App\Http\Middleware\CheckActiveUserMiddleware;
use Illuminate\Support\Facades\Route;


Route::get('/report-bol/{batch}', [BillOfLadingController::class, 'reportBol']);
Route::post('sms-gateway', SendSmsGatewayController::class);
Route::get('dl-bom/{po}', ExportBomController::class)->name('dl.bom');

Route::get('/block-history/{vehicle}', BlockHistoryController::class);
Route::any('/', function () {
    return response()->json(['version' => app()->version(), 'env' => app()->environment()]);
});
Route::get('/print-synchronous/{synchronous}',
    [SynchronousController::class, 'printSynchronous'])->name('print-synchronous');

Route::get('/synchronous/{number}',
    [SynchronousController::class, 'getBySynchronousNumber'])->name('search-synchronous-by-number');
Route::put('/synchronous-items/{synchronousItem}',
    [SynchronousController::class, 'updateCrossReference'])->name('update-synchronous-item');

Route::get('check-login', [AuthController::class, 'checkLogin'])->name('check-login');
Route::get('/display-board/{salon}', DisplayBoardController::class)->name('display-board');
Route::post('auth/login', [AuthController::class, 'login']);
Route::get('auth/check-username', [AuthController::class, 'checkUserName']);
Route::post('/recover-password', [AuthController::class, 'recoverPassword']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::get('/dl', DownloadController::class)->name('dl');
Route::get('dl-file', [DownloadController::class, 's3'])->name('dl-s3');
Route::middleware(['auth:sanctum', CheckActiveUserMiddleware::class])->group(function () {

    Route::resource('/change-vins', ChangeVinController::class)->only(['index', 'store']);
    Route::apiResource('enter-engines', EnterEngineController::class)->except(['destroy', 'update']);

    Route::get('/info-barcode/{code}', [BarcodeController::class, 'getInfo'])->name('info-barcode');
    Route::delete('/production-tracking/{tracking}',
        [ProductionTrackingController::class, 'destroy'])->name('production-tracking-destroy');
    Route::get('/system-block-items', [BlockItemController::class, 'systemBlocks'])->name('system-block-items');
    Route::post('/basket-batch-update', [BasketController::class, 'batchUpdate'])->name('basket-batch-update');
    Route::get('/erp-jobs', [ErpJobController::class, 'index'])->name('erp-jobs');
    Route::put('/erp-jobs/{erpJob}', [ErpJobController::class, 'retry'])->name('retry-erp-jobs');
    Route::post('/basket-batch-update', [BasketController::class, 'batchUpdate'])->name('basket-batch-update');
    Route::get('/vehicle-shortages', [ShortageController::class, 'vehicleShortages'])->name('vehicle-shortages');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('/synchronous-other', [SynchronousController::class, 'otherIndex'])->name('index.other-synchronous');
    Route::get('/synchronous-engine', [SynchronousController::class, 'engineIndex'])->name('index.engine-synchronous');

    Route::patch('/close-synchronous/{synchronous}',
        [SynchronousController::class, 'closeSynchronous'])->name('close-synchronous');


    Route::post('/return-container/{billOfLading}',
        [BillOfLadingController::class, 'returnContainer'])->name('return-container');
    Route::get('/erp-product-items/{erpProductItem}/cross-references',
        [ErpProductItemController::class, 'crossReferences'])->name('erp-product-items.cross-references');
    Route::post('/register-cross-reference/{synchronousType}',
        [SynchronousTypeItemController::class, 'store'])->name('register-cross-reference');
    Route::delete('/synchronous-type-item/{synchronousTypeItem}',
        [SynchronousTypeItemController::class, 'destroy'])->name('synchronous-type-item.destroy');

    Route::get('/custom-locations', CustomLocationController::class)->name('get-custom-locations');
    Route::get('/report-block-items', [BlockItemController::class, 'report'])->name('report-block-items');
    Route::get('/report-vehicle-qc', [VehicleController::class, 'reportVehicleQc'])->name('report-vehicle-qc');
    Route::get('/vehicle-qc', [VehicleController::class, 'vehicleQc'])->name('vehicle-qc');
    Route::get('/report-incidents', [IncidentController::class, 'report'])->name('report-incidents');
    Route::get('/report-production-line-activities',
        [ProductionLineActivityController::class, 'report'])->name('report-production-line-activities');
    Route::get('/report-barcodes', [BarcodeController::class, 'report'])->name('report-barcodes');
    Route::post('/upload', [FileController::class, 'upload'])->name('upload.store');
    Route::get('/tracking-history/{modelType}/{modelId}/{type}',
        TrackingHistoryController::class)->name('tracking-history');
    Route::get('/get-template-incident', [IncidentController::class, 'getTemplate']);
    Route::get('/get-template-vin-engines', [VinEngineController::class, 'getTemplate']);
    Route::get('/get-template-element', [ElementController::class, 'getTemplate']);
    Route::get('/get-template-unblock-items', [BlockItemController::class, 'getTemplateUnblockItems']);

    Route::get('/get-template-bill-of-lading-for-vehicle',
        [BillOfLadingController::class, 'getTemplateBillOfLadingForVehicle']);

    Route::get('/get-template-bill-of-lading-for-container',
        [BillOfLadingController::class, 'getTemplateBillOfLadingForContainer']);

    Route::post('/import-bill-of-lading',
        [BillOfLadingController::class, 'importBillOfLading'])->name('import.bill-of-lading');
    Route::post('/import-vin-engines',
        [VinEngineController::class, 'importer'])->name('import.vin-engines');

    Route::get('/get-stop-locations',
        [SubmitIncidentLocationController::class, 'getStopLocations'])->name('get.stop.locations');

    Route::get('channels', JoinChannelController::class);


    Route::get('/station/{station}/users', [StationController::class, 'users'])->name('station.users');
    Route::post('/station/{station}/users', [StationController::class, 'assignUser'])->name('station.assign.user');
    Route::delete('/station/{station}/users/{user}',
        [StationController::class, 'removeUser'])->name('station.remove.user');

    Route::get('/stop-fact/{stopFact}/users', [StopFactController::class, 'users'])->name('stop-fact.users');
    Route::post('/stop-fact/{stopFact}/users',
        [StopFactController::class, 'assignUser'])->name('stop-fact.assign.user');
    Route::delete('/stop-fact/{stopFact}/users/{user}',
        [StopFactController::class, 'removeUser'])->name('stop-fact.remove.user');


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


    Route::get('/sale-company/{saleCompany}/users',
        [SaleCompanyController::class, 'users'])->name('sale-company.users');

    Route::post('/sale-company/{saleCompany}/users',
        [SaleCompanyController::class, 'assignUser'])->name('sale-company.assign.user');

    Route::delete('/sale-company/{saleCompany}/users/{user}',
        [SaleCompanyController::class, 'removeUser'])->name('sale-company.remove.user');


    Route::get('/production-line/{productionLine}/users',
        [ProductionLineController::class, 'users'])->name('production-line.users');
    Route::post('/production-line/{productionLine}/users',
        [ProductionLineController::class, 'assignUsers'])->name('production-line.assign.user');
    Route::delete('/production-line/{productionLine}/users/{user}',
        [ProductionLineController::class, 'removeUser'])->name('production-line.remove.user');


    Route::get('/setting/{setting}/users',
        [SettingController::class, 'users'])->name('setting.users');
    Route::post('/setting/{setting}/users',
        [SettingController::class, 'assignUsers'])->name('setting.assign.user');
    Route::delete('/setting/{setting}/users/{user}',
        [SettingController::class, 'removeUser'])->name('setting.remove.user');


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
    Route::post('/torque-test', [VehicleTorqueTestController::class, 'store'])->name('vehicles.torque-test');
    Route::delete('/torque-test/{vehicleTorqueTest}',
        [VehicleTorqueTestController::class, 'destroy'])->name('vehicles.torque-test.destroy');
    Route::post('/report-shortages', [ShortageController::class, 'reportShortages'])->name('report-shortages');
    Route::get('options/{resource}', OptionController::class)->middleware(['CheckAccessUser']);
    Route::middleware('access.vehicle')->group(function () {
        Route::prefix('vehicles/{vehicle}')->group(function () {
            Route::get('action-logs', [ActionLogController::class, 'getList'])->name('vehicle.action.logs');
            Route::get('/screws', [ScrewController::class, 'listScrewsForVin'])->name('vehicles.screws');
            Route::get('/torque-tests', [ScrewController::class, 'torqueTests'])->name('vehicles.torque-tests');
            Route::post('/generate-cabin-numbers',
                [CabinNumberController::class, 'generate'])->name('generate.cabin-numbers');
            Route::apiResource('shortages', ShortageController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::get('/main-component-items',
                [ComponentItemController::class, 'main'])->name('vehicles.main-component-items');
            Route::get('/all-component-items',
                [ComponentItemController::class, 'all'])->name('vehicles.all-component-items');
            Route::get('overview', VehicleOverviewController::class)->name('vehicle.overview');

            Route::post('engine-barcode', SubmitEngineBarcodeController::class)->name('submit.engine.barcode');

            Route::post('/gate-permit-exit',
                GatePermitTransferController::class)->withoutMiddleware('access.vehicle')->name('gate-permit-exit');


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
        'prefix'     => 'production-lines/{production_line}',
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
//    Route::post('/old-gate/{vehicle}', [GatePermitController::class, 'oldGate'])->name('old-gate');

    Route::get('/report-excel-tracking',
        [ProductionTrackingController::class, 'reportExcel'])->name('report-excel-tracking');

    Route::get('/report-excel-change-vins',
        [ChangeVinController::class, 'reportExcel'])->name('report-excel-change-vins');

    Route::get('/report-excel-synchronous-engine',
        [SynchronousController::class, 'reportExcel'])->name('report-excel-synchronous-engine');

    Route::get('/report-excel-tracking-exit-door',
        [ProductionTrackingController::class, 'reportExcelExitDoor'])->name('report-excel-tracking-exit-door');

    Route::get('/report-excel-tracking-sale-locations',
        [ProductionTrackingController::class, 'reportExcelSaleLocation'])->name('report-excel-tracking-sale-locations');

    Route::get('/report-excel-bill-of-ladings',
        [BillOfLadingController::class, 'reportExcel'])->name('report-excel-bill-of-ladings');

    Route::patch('/erp-jobs/{erpJob}/change-status-to-ignore',
        [ErpJobController::class, 'changeStatusToIgnore'])->name('erp-job.change-status');
    Route::get('/report-excel-test-results',
        [ProductionTestResultController::class, 'reportExcel'])->name('report-excel-test-results');


    Route::get('/report-excel-gate-permits',
        [GatePermitController::class, 'reportExcel'])->name('report-excel-gate-permits');
    Route::apiResource('sale-invoices', SaleInvoicesController::class)->only('index');


    Route::post('/add-pallet', [PalletController::class, 'addPallet'])->name('add-pallet');
    Route::post('/transfer-pallet', [PalletController::class, 'transferPallet'])->name('transfer-pallet');
    Route::get('/pallets/{pallet}/locations',
        [PalletController::class, 'getPalletLocations'])->name('pallet-locations');


    Route::get('list-sale-locations',
        [SaleLocationController::class, 'listSaleLocations'])->name('list-sale-locations');

    Route::get('gate-permits/{gatePermit}/print', [GatePermitController::class, 'print'])->name('gate-permits.print');

    Route::get('/general-product-items/car-model-id/{carModelId}',
        [GeneralProductItemController::class, 'carModelItems'])->name('general-product-items.car-model-id');

    Route::get('/vehicle/{vehicle}/general-product-items',
        [GeneralProductItemController::class, 'vehicleItems'])->name('general-product-items.vehicle');

    Route::apiResource('pallet-transactions', PalletTransactionController::class)->except('destroy');

    Route::apiResource('cabin-numbers', CabinNumberController::class)->only(['index', 'show']);

    Route::get('/report-excel-cabin-numbers',
        [CabinNumberController::class, 'reportExcel'])->name('export-cabin-numbers');
    Route::get('/report-excel-pallet-transactions',
        [PalletController::class, 'reportExcelTransactions'])->name('export-pallet-transactions');
    Route::get('/report-excel-pallet-stocks',
        [PalletController::class, 'reportExcelStocks'])->name('export-pallet-stocks');
    Route::get('/report-excel-vehicle-torque-test',
        [ScrewController::class, 'reportExcel'])->name('export-vehicle-torque-test');


    Route::apiResource('settings', SettingController::class)->except('store', 'destroy');

    Route::get('/torque-test-groups/{torqueTestGroup}/screws',
        [TorqueTestGroupController::class, 'screws'])->name('torque-group-screws');
    Route::get('/torque-test-groups/{torqueTestGroup}/get-tests',
        [TorqueTestGroupController::class, 'tests'])->name('torque-group-tests');
    Route::apiResources([
        'roles'                       => RoleController::class,
        'make-codes'                  => MakeCodeController::class,
        'car-models'                  => CarModelController::class,
        'users'                       => UserController::class,
        'salons'                      => SalonController::class,
        'production-lines'            => ProductionLineController::class,
        'work-group-centers'          => WorkGroupCenterController::class,
        'stations'                    => StationController::class,
        'incidents'                   => IncidentController::class,
        'elements'                    => ElementController::class,
        'block-reasons'               => BlockReasonController::class,
        'transit-companies'           => TransitCompanyController::class,
        'cars'                        => CarController::class,
        'stop-facts'                  => StopFactController::class,
        'stop-reasons'                => StopReasonController::class,
        'stop-types'                  => StopTypeController::class,
        'barcodes'                    => BarcodeController::class,
        'baskets'                     => BasketController::class,
        'inventories'                 => InventoryController::class,
        'containers'                  => ContainerController::class,
        'drivers'                     => DriverController::class,
        'sale-locations'              => SaleLocationController::class,
        'production-test-results'     => ProductionTestResultController::class,
        'accompanying-items'          => AccompanyingItemController::class,
        'locations'                   => LocationController::class,
        'sale-companies'              => SaleCompanyController::class,
        'general-product-items'       => GeneralProductItemController::class,
        'synchronous-type'            => SynchronousTypeController::class,
        'production-tests'            => ProductionTestController::class,
        'incident-categories'         => IncidentCategoryController::class,
        'pallet-types'                => PalletTypeController::class,
        'pallets'                     => PalletController::class,
        'color-maps'                  => ColorMapController::class,
        'year-maps'                   => YearMapController::class,
        'body-options'                => BodyOptionController::class,
        'torque-measuring-equipments' => TorqueMeasuringEquipmentController::class,
        'screws'                      => ScrewController::class,
        'qc-codes'                    => QcCodeController::class,
        'torque-coefficients'         => TorqueCoefficientController::class,
        'production-checklists'       => ProductionChecklistController::class,
        'item-input-checklists'       => ItemInputChecklistController::class,
        'cop-plans'                   => CopPlanController::class,
        'item-technical-checklists'   => ItemTechnicalChecklistController::class,
        'product-boms'                => ProductBomController::class,
        'production-control'          => ProductionControlController::class,
        'pdi-check-lists'             => PdiCheckListController::class,
        'vin-engines'                 => VinEngineController::class,
        'car-model-color-codes'       => CarModelColorCodeController::class,
        'torque-meters'               => TorqueMeterController::class,
        'torque-test-groups'          => TorqueTestGroupController::class
    ]);

    /*
     * Agency panel
     */

    Route::get('incoming-vehicles', IncomingVehicleController::class)->name('incoming-vehicles');
    Route::get('incoming-vehicles/{vehicle}',
        [ReceiptVehicleController::class, 'get'])->name('get-receipt-vehicle-information');
    Route::post('incoming-vehicles/{vehicle}', [ReceiptVehicleController::class, 'set'])->name('set-receipt-vehicle');


    Route::get('location-vehicles', LocationVehicleController::class)->name('location-vehicles');
    Route::get('deliver-vehicle/{vehicle}',
        [DeliverVehicleToCustomerController::class, 'get'])->name('get-deliver-vehicle-information');
    Route::post('deliver-vehicle/{vehicle}',
        [DeliverVehicleToCustomerController::class, 'set'])->name('set-deliver-vehicle-information');


    Route::get('pending-invoices', PendingInvoiceController::class)->name('sales-company.pending-invoices');
});

Route::prefix('sb')->group(function () {
    Route::prefix('{companyPrefix}')->group(function () {
        Route::get('/', [SalesBridgeController::class, 'getOrderIds']);
        Route::get('/{id}', [SalesBridgeController::class, 'getOrderDetail']);
        Route::get('/cf/{reference}', [SalesBridgeController::class, 'getCustomerFinance']);
        Route::get('/sp/{id}', [SalesBridgeController::class, 'getSecondPayment']);
    })->whereIn('companyPrefix', ['l', 'd', 'p', 'a']);
});

