<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\RegisterShortageReportExport;
use App\Models\RegisterShortage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportRegisterShortageService extends BaseExporterService
{
    public function __construct(public $job) {}

    public function export(): string
    {
        $rows = RegisterShortage::getReportData((array) $this->job->getAttribute('data'));
        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'register-shortages-'.Str::uuid().'.xlsx';
        Excel::store(
            new RegisterShortageReportExport($rows, $eventData),
            '/public/exports/'.$fileName
        );

        return $fileName;
    }
}
