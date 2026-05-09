<?php

namespace App\Services\Exporter;


use Exception;

class ExporterServiceFactory
{


    /**
     * @throws Exception
     */
    public static function getInstance($job)
    {
        logger('is here : getInstance');
        return match ($job->type) {
            'export-block-items' => new ExportBlockItemsService($job),
            'export-general-product-items' => new ExportGeneralProductItemsService($job),
            'export-production-test-results' => new ExportProductionTestResultService($job),
            'export-tracking', 'export-tracking-sale-location' => new ExportTrackingService($job),
            'export-tracking-exit-door' => new ExportTrackingExitDoorService($job),
            'export-vehicle-qc' => new ExportVehicleQcService($job),
            'export-bill-of-ladings' => new ExportBillOfLadingsService($job),
            'export-gate-permits' => new ExportGatePermitsService($job),
            'export-barcodes' => new ExportBarcodeService($job),
            'export-faults-qc' => new ExportFaultService($job, 'qc'),
            'export-faults-evaluation' => new ExportFaultService($job, 'evaluation'),
            'export-faults-pdi' => new ExportFaultService($job, 'pdi'),
            'export-incidents' => new ExportIncidentsService($job),
            'export-production-line-activities' => new ExportProductionLineActivityService($job),
            'export-shortages' => new ExportShortageService($job),
            'export-cabin-numbers' => new ExportCabinNumberService($job),
            'export-pallet-transactions' => new ExportPalletTransactionService($job),
            'export-pallet-stocks' => new ExportPalletStockService($job),
            'export-vehicle-torque-test' => new ExportVehicleTorqueTestService($job),
            'export-change-vins' => new ExportChangeVinService($job),
            'export-synchronous-engine' => new ExportSynchronousEngineService($job),
            default => throw  new Exception('Invalid input for type: '.$job->type),
        };
    }
}
