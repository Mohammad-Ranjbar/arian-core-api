<?php

namespace App\Services\Importer;

use App\Events\JobProcessEvent;
use App\Models\File;

class ImportIncidentService extends BaseImporterService
{
    public function import($jobId, File $file)
    {
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
            $result['done']++;

            if (true) {
                $result['success']++;
            } else {
                $result['error']++;
            }
            JobProcessEvent::dispatch($result);
        }
    }
}