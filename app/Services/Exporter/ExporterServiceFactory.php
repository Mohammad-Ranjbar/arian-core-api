<?php

namespace App\Services\Exporter;


use Exception;

class ExporterServiceFactory
{

    /**
     * @param $job
     * @return ExportBlockItemsService|ExportFaultService|ExportIncidentsService|ExportProductionLineActivityService|ExportVehicleQcService
     * @throws Exception
     */
    public static function getInstance($job): ExportProductionLineActivityService|ExportFaultService|ExportVehicleQcService|ExportIncidentsService|ExportBlockItemsService
    {
        return match ($job->type) {
            'export-block-items' => new ExportBlockItemsService($job),
            'export-vehicle-qc' => new ExportVehicleQcService($job),
            'export-faults-qc' => new ExportFaultService($job,'qc'),
            'export-faults-evaluation' => new ExportFaultService($job,'evaluation'),
            'export-incidents' => new ExportIncidentsService($job),
            'export-production-line-activities' => new ExportProductionLineActivityService($job),
            default => throw  new Exception('Invalid input for type: ' . $job->type .'by user:'.auth()->user()->name),
        };
    }
}
