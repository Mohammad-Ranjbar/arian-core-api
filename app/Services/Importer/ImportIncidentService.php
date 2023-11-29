<?php

namespace App\Services\Importer;

use App\Events\JobProcessEvent;
use App\Models\File;
use Maatwebsite\Excel\Facades\Excel;


class ImportIncidentService extends BaseImporterService
{
    public function import($jobId, File $file)
    {
        $rows = Excel::load(public_path($file->path))->get();
        
        dd($rows);

        $rows = [1, 2, 3];

        $result = [
            'id'      => $jobId,
            'user_id' => $file->getAttribute('user_id'),
            'total'   => count($rows),
            'done'    => 0,
            'success' => 0,
            'error'   => 0
        ];
        JobProcessEvent::dispatch($result);

        foreach ($rows as $row) {
            if (true) {
                //imported
                $result['success']++;
            } else {
                $result['error']++;
            }

            $result['done']++;
            JobProcessEvent::dispatch($result);
        }

        return [
            // result: File path or data
        ];
    }
}