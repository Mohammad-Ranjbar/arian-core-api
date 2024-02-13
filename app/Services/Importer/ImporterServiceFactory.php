<?php

namespace App\Services\Importer;


class ImporterServiceFactory
{
    public static function getInstance($type): ImportIncidentService|string
    {
        return match ($type) {
            'import-incidents'      => new ImportIncidentService(),
            'import-element'        => new ImportElementService(),
            'import-block-vehicles' => new ImportBlockVehicleService(),
            'import-unblock-items'  => new ImportUnblockItemsService(),
            'import-block_items'    => new ImportBlockItemsService(),
            default                 => 'Invalid input',
        };
    }
}
