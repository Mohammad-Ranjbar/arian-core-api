<?php

namespace App\Services\Importer;


class ImporterServiceFactory
{
    public static function getInstance($type
    ): ImportBlockVehicleService|ImportBlockItemsService|ImportIncidentService|string|ImportElementService|ImportUnblockItemsService {
        return match ($type) {
            'import-incidents' => new ImportIncidentService(),
            'import-elements' => new ImportElementService(),
            'import-block-vehicles' => new ImportBlockVehicleService(),
            'import-unblock_items' => new ImportUnblockItemsService(),
            'import-block_items' => new ImportBlockItemsService(),
            'import-bill_of_ladings' => new ImportBillOfLadingService(),
            default => 'Invalid input',
        };
    }
}
