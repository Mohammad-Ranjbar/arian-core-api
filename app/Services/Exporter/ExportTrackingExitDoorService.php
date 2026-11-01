<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\TrackingExitDoorReportExport;
use App\Models\Tracking;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;

class ExportTrackingExitDoorService extends BaseExporterService
{

    public function __construct(public $job)
    {

    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(): string
    {
        $rows = Tracking::getReportExitDoorData((array)$this->job->getAttribute('data'));

        $eventData = [
            'id'      => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total'   => $rows->count(),
            'done'    => 0,
            'success' => 0,
            'error'   => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'trackingExitDoorExport-'.Str::uuid().'.xlsx';
        $path = '/public/exports/'.$fileName;
        Excel::store(new TrackingExitDoorReportExport($rows, $eventData), $path);
        return $fileName;

    }
}
