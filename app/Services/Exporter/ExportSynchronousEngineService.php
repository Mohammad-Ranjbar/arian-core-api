<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\ChangeVinReportExport;
use App\Exports\SynchronousEngineReportExport;
use App\Models\Synchronous;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportSynchronousEngineService extends BaseExporterService
{
    /**
     * @param $job
     */
    public function __construct(public $job)
    {

    }

    public function export(): string
    {

        $rows = Synchronous::query()->with('synchronousType')
            ->whereHas('synchronousType', function ($q) {
                $q->where('type', 'engine');
            })
            ->get();
        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'synchronousEngine-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new SynchronousEngineReportExport($rows, $eventData), $path);
        return $fileName;
    }
}
