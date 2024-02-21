<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\BlockItemReportExport;
use App\Repositories\BlockItemRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportBlockItemsService extends BaseExporterService
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
        request()->merge((array) $this->job->getAttribute('data'));
        $rows = app()->make(BlockItemRepository::class)->get();

        $eventData = [
            'id'      => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total'   => $rows->count(),
            'done'    => 0,
            'success' => 0,
            'error'   => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'blockItemExport-'.Str::uuid().'.xlsx';
        $path = '/public/exports/'.$fileName;
        Excel::store(new BlockItemReportExport($rows, $eventData), $path);
        return asset('/storage/exports/'.$fileName);

    }
}