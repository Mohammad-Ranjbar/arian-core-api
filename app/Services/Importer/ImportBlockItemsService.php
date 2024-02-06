<?php

namespace App\Services\Importer;

use App\Imports\BlockItemImport;
use App\Imports\UnblockItemImport;
use App\Models\File;
use Maatwebsite\Excel\Facades\Excel;


class ImportBlockItemsService extends BaseImporterService
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

        Excel::import(new BlockItemImport($data), public_path($file->getAttribute('path')));

    }
}
