<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\FaultReportExport;
use App\Exports\VehicleQcReportExport;
use App\Models\VehicleIncident;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportFaultQcService extends BaseExporterService
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
        $data = (array)$this->job->getAttribute('data');

        request()->merge($data);
        if (!isset($data['from'])) {
            $data['from'] = now()->subCentury();
        }
        if (!isset($data['to'])) {
            $data['to'] = now()->addCentury();
        }
        if ($data['date_type'] == 'create') {
            $rows = VehicleIncident::query()
                ->whereBetween('created_at', [$data['from'], $data['to']])
                ->where('type', 'qc')
                ->whereHas('vehicle', function ($query) use ($data) {
                    $query->whereIn('make_code_id', $data['make_code'])
                        ->whereIn('car_model_id', $data['car_model']);
                })
                ->get();
        } elseif ($data['date_type'] == 'check') {
            $rows = VehicleIncident::query()
                ->whereBetween('checked_at', [$data['from'], $data['to']])
                ->where('type', 'qc')
                ->whereHas('vehicle', function ($query) {
                    $query->whereIn('make_code_id', $data['make_code'])
                        ->whereIn('car_model_id', $data['car_model']);
                })
                ->get(['checked_at as created_at']);
        }


        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'exportFaultsQc-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new FaultReportExport($rows, $eventData), $path);
        return asset('/storage/exports/' . $fileName);

    }
}
