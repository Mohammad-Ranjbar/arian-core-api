<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\GeneralProductItemReportExport;
use App\Models\GeneralProductItem;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportGeneralProductItemsService extends BaseExporterService
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
        $rows = GeneralProductItem::getReportData((array)$this->job->getAttribute('data'));

        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'generalProductItemsExport-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new GeneralProductItemReportExport($rows, $eventData), $path);
        return $fileName;
    }
}
