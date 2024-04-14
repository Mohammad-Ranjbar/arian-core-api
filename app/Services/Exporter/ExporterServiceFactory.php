<?php

namespace App\Services\Exporter;


class ExporterServiceFactory
{

    public static function getInstance($job)
    {
        return match ($job->type) {
            'export-block-items' => new ExportBlockItemsService($job),
            'export-incidents' => new ExportIncidentsService($job),
            default => 'Invalid input',
        };
    }
}
