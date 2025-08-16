<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\ChangeVinReportExport;
use App\Exports\VehicleTorqueTestReportExport;
use App\Models\ChangeVin;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportChangeVinService extends BaseExporterService
{
    /**
     * @param $job
     */
    public function __construct(public $job)
    {

    }

    public function export(): string
    {

        $rows = ChangeVin::query()->get();
        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'changeVins-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new ChangeVinReportExport($rows, $eventData), $path);
        return $fileName;
    }
}
