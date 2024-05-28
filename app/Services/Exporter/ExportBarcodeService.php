<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\BarcodeReportExport;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportBarcodeService extends BaseExporterService
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
//        $rows = Barcode::getReportData((array)$this->job->getAttribute('data'));

        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => 0,
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'barcodeExport-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;

        Excel::store(new BarcodeReportExport((array)$this->job->getAttribute('data'), $eventData), $path);
        return $fileName;

    }
}
