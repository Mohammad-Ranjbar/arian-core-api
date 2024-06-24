<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\ProductionLineActivityReportExport;
use App\Models\ProductionLineActivity;
use App\Repositories\ProductionLineActivityRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductionLineActivityService extends BaseExporterService
{
    /**
     * @param $job
     */
    public function __construct(public $job)
    {

    }

    /**
     * @return string
     * @throws BindingResolutionException
     */
    public function export(): string
    {
        $rows= ProductionLineActivity::getReportData((array) $this->job->getAttribute('data'));
        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'productionLineActivityExport-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new ProductionLineActivityReportExport($rows, $eventData), $path);

        return $fileName;
    }
}
