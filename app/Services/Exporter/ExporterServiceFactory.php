<?php

namespace App\Services\Exporter;


use Exception;

class ExporterServiceFactory
{

    /**
     * @throws Exception
     */
    public static function getInstance($job): ExportProductionLineActivityService|ExportTrackingService|ExportVehicleQcService|ExportIncidentsService|ExportFaultService|ExportBlockItemsService
    {
        return match ($job->type) {
            'export-block-items' => new ExportBlockItemsService($job),
            'export-tracking','export-tracking-sale-location' => new ExportTrackingService($job),
            'export-vehicle-qc' => new ExportVehicleQcService($job),
            'export-barcodes' => new ExportBarcodeService($job),
            'export-faults-qc' => new ExportFaultService($job, 'qc'),
            'export-faults-evaluation' => new ExportFaultService($job, 'evaluation'),
            'export-incidents' => new ExportIncidentsService($job),
            'export-production-line-activities' => new ExportProductionLineActivityService($job),
            default => throw  new Exception('Invalid input for type: ' . $job->type . 'by user:' . auth()->user()->name),
        };
    }
}
