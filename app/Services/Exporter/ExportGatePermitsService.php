<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\GatePermitReportExport;
use App\Repositories\GatePermitRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportGatePermitsService extends BaseExporterService
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

        $rows = app()->make(GatePermitRepository::class)
            ->getReportData((array)$this->job->getAttribute('data'));

        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'export-gate-permits-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new GatePermitReportExport($rows, $eventData), $path);
        return $fileName;

    }
}
