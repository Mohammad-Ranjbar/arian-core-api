<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\TrackingReportExport;
use App\Models\Tracking;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportTrackingService extends BaseExporterService
{

    public function __construct(public $job)
    {

    }

    /**
     * @return string
     * @throws BindingResolutionException
     */
    public function export(): string
    {
        logger('is here');
        $rows = Tracking::getReportData((array)$this->job->getAttribute('data'));

        $eventData = [
            'id'      => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total'   => $rows->count(),
            'done'    => 0,
            'success' => 0,
            'error'   => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'trackingExport-'.Str::uuid().'.xlsx';
        $path = '/public/exports/'.$fileName;
        Excel::store(new TrackingReportExport($rows, $eventData), $path);
        return $fileName;

    }
}
