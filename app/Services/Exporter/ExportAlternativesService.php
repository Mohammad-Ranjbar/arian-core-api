<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\AlternativeReportExport;
use App\Models\Alternative;
use App\Services\AlternativeFilters;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ExportAlternativesService extends BaseExporterService
{
    public function __construct(public $job) {}

    public function export(): string
    {
        $rows = AlternativeFilters::apply(Alternative::query(), (array) $this->job->getAttribute('data'))
            ->with(['alternativeItem.erpProductItem', 'variants', 'user'])->latest()->get();
        $eventData = ['id' => $this->job->uuid, 'user_id' => $this->job->user_id, 'total' => $rows->count(), 'done' => 0, 'success' => 0, 'error' => 0];
        event(new JobProcessEvent($eventData));
        $filename = 'alternativesExport-'.Str::uuid().'.xlsx';
        if (! Excel::store(new AlternativeReportExport($rows, $eventData), '/public/exports/'.$filename)) {
            throw new RuntimeException('ذخیره گزارش الترناتیو ناموفق بود.');
        }

        return $filename;
    }
}
