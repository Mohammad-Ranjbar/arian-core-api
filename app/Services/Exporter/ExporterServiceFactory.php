<?php

namespace App\Services\Exporter;


use Exception;
use Illuminate\Support\Facades\Auth;

class ExporterServiceFactory
{


    /**
     * @throws Exception
     */
    public static function getInstance($job)
    {
        return match ($job->type) {
            'export-block-items' => new ExportBlockItemsService($job),
            'export-production-test-results' => new ExportProductionTestResultService($job),
            'export-tracking', 'export-tracking-sale-location' => new ExportTrackingService($job),
            'export-vehicle-qc' => new ExportVehicleQcService($job),
            'export-bill-of-ladings' => new ExportBillOfLadingsService($job),
            'export-gate-permits' => new ExportGatePermitsService($job),
            'export-barcodes' => new ExportBarcodeService($job),
            'export-faults-qc' => new ExportFaultService($job, 'qc'),
            'export-faults-evaluation' => new ExportFaultService($job, 'evaluation'),
            'export-incidents' => new ExportIncidentsService($job),
            'export-production-line-activities' => new ExportProductionLineActivityService($job),
            'export-shortages' => new ExportShortageService($job),
            'export-cabin-numbers' => new ExportCabinNumberService($job),
            'export-pallet-transactions' => new ExportPalletTransactionService($job),
            default => throw  new Exception('Invalid input for type: ' . $job->type . 'by user id:' . Auth::id()),
        };
    }
}
