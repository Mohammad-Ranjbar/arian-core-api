<?php

namespace App\Services\Exporter;


class ExporterServiceFactory
{
    /**
     * @param $job
     * @return string|ExportBlockItemsService
     */
    public static function getInstance($job): string|ExportBlockItemsService
    {
        return match ($job->type) {
            'export-block-items' => new ExportBlockItemsService($job),
            default => 'Invalid input',
        };
    }
}
