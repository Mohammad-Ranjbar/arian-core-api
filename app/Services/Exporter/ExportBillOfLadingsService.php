<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\BillOfLadingReportExport;
use App\Models\BillOfLading;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportBillOfLadingsService extends BaseExporterService
{

    /**
     * @param $job
     * @param $type
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

        $rows = BillOfLading::getReportData($this->job->getAttribute('data'));

        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'export-bill-of-ladings-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new BillOfLadingReportExport($rows, $eventData), $path);
        return $fileName;

    }
}
