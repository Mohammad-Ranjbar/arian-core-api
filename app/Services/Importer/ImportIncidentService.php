<?php

namespace App\Services\Importer;

use App\Jobs\ImportJob;

class ImportIncidentService extends BaseImporterService
{
    public function import($data)
    {
       ImportJob::dispatch($data->getAttribute('uuid'));
    }
}