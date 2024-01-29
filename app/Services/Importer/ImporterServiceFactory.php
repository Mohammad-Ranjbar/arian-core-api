<?php

namespace App\Services\Importer;


class ImporterServiceFactory
{
    public static function getInstance($model): ImportIncidentService|string
    {
        return match ($model) {
            'import-incidents' => new ImportIncidentService(),
            'import-element' => new ImportElementService(),
            'import-block-vehicles' => new ImportBlockVehicleService(),
            default => 'Invalid input',
        };
    }
}
