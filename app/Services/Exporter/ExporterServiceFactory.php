<?php

namespace App\Services\Exporter;


class ExporterServiceFactory
{

    public static function getInstance($job)
    {
        return match ($job->type) {
            'export-block-items' => new ExportBlockItemsService($job),
            'export-vehicle-qc' => new ExportVehicleQcService($job),
            'export-incidents' => new ExportIncidentsService($job),
            'export-production-line-activities' => new ExportProductionLineActivityService($job),
            default => 'Invalid input',
        };
    }
}
