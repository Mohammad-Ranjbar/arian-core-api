<?php

namespace App\Services\Importer;

use App\Imports\IncidentImport;
use App\Models\File;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;


class ImportIncidentService extends BaseImporterService
{
    public function import($jobId, File $file)
    {
        $data = [
            'id'      => $jobId,
            'user_id' => $file->getAttribute('user_id'),
            'total'   => 0,
            'done'    => 0,
            'success' => 0,
            'error'   => 0,
        ];

        Excel::import(new IncidentImport($data), public_path($file->getAttribute('path')));

    }
}