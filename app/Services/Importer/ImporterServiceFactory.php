<?php

namespace App\Services\Importer;


class ImporterServiceFactory
{
    public static function getInstance($model): ImportIncidentService|string
    {
        return match ($model) {
            'import-incidents' => new ImportIncidentService(),

            default => 'Invalid input',
        };
    }
}
