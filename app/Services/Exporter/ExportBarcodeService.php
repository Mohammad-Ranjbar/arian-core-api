<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\BarcodeCsvExport;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;

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

        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => 0,
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'barcodeExport-' . Str::uuid() . '.csv';
        $path = 'app/public/exports/' . $fileName;

        $exporter = new BarcodeCsvExport();

        $exporter->make((array)$this->job->getAttribute('data'), $path, $eventData);

//        Excel::store(new BarcodeReportExport((array)$this->job->getAttribute('data'), $eventData), $path);
        return $fileName;

    }
}
