<?php

namespace App\Services\Importer;

use App\Imports\BlockItemImport;
use App\Imports\UnblockItemImport;
use App\Models\File;
use App\Models\Job;
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
        $job = Job::query()->find($jobId);
        $jabData = $job->getAttribute('data');
        Excel::import(new BlockItemImport($data, $jabData['description'],  $jabData['block_reason_id']), public_path($file->getAttribute('path')));

    }
}
