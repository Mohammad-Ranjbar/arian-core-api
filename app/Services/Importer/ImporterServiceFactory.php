<?php

namespace App\Services\Importer;


use Exception;

class ImporterServiceFactory
{

    /**
     * @throws Exception
     */
    public static function getInstance($type
    )
    {
        return match ($type) {
            'import-incidents' => new ImportIncidentService(),
            'import-elements' => new ImportElementService(),
            'import-block-vehicles' => new ImportBlockVehicleService(),
            'import-unblock_items' => new ImportUnblockItemsService(),
            'import-block_items' => new ImportBlockItemsService(),
            'import-bill_of_ladings' => new ImportBillOfLadingService(),
            'import-vin_engines' => new ImportVinEnginesService(),
            'import-engine' => new ImportEngineService(),
            'import-police' => new ImportPoliceService(),
            'import-pdi' => new ImportPdiService(),
            default => throw new Exception('not found importer'),
        };
    }
}
