<?php

namespace App\Services\Importer;

use App\Imports\EngineImport;
use App\Imports\VinEngineImport;
use App\Models\File;
use Maatwebsite\Excel\Facades\Excel;


class ImportEngineService extends BaseImporterService
{
    public function import($jobId, File $file): void
    {
        $data = [
            'id'      => $jobId,
            'user_id' => $file->getAttribute('user_id'),
            'total'   => 0,
            'done'    => 0,
            'success' => 0,
            'error'   => 0,
        ];

        Excel::import(new EngineImport($data), storage_path("app/public/".$file->getAttribute('path')));
    }
}
