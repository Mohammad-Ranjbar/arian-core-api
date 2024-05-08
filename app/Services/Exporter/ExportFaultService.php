<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\FaultReportExport;
use App\Repositories\VehicleIncidentRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportFaultService extends BaseExporterService
{

    /**
     * @param $job
     * @param $type
     */
    public function __construct(public $job, private $type)
    {

    }

    /**
     * @return string
     * @throws BindingResolutionException
     */
    public function export(): string
    {
        /** @var VehicleIncidentRepository $repo */
        $repo = app()->make(VehicleIncidentRepository::class);
        $rows = $repo->getReportData($this->type,(array)$this->job->getAttribute('data'));

        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'export-faults-'.$this->type.'-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new FaultReportExport($rows, $eventData), $path);
        return $fileName;

    }
}
