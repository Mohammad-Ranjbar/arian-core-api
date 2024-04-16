<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\BlockItemReportExport;
use App\Exports\VehicleQcReportExport;
use App\Models\ActionLog;
use App\Models\Vehicle;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportVehicleQcService extends BaseExporterService
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
        if (!isset($data['created_at_from'])){
            $data['created_at_from'] = now()->subCentury();
        }
        if (!isset($data['created_at_to'])){
            $data['created_at_to'] = now()->addCentury();
        }
        $rows = ActionLog::query()->where('model_type', Vehicle::class)
            ->where('created_at', '>=', $data['created_at_from'])
            ->where('created_at', '<=', $data['created_at_to'])
            ->whereHasMorph('model',[Vehicle::class], function ($query) use ($data) {
                $query->where('make_code_id', $data['make_code_id'])
                    ->where('car_model_id', $data['car_model_id']);
            })->get();

        $eventData = [
            'id' => $this->job->uuid,
            'user_id' => $this->job->user_id,
            'total' => $rows->count(),
            'done' => 0,
            'success' => 0,
            'error' => 0,
        ];
        event(new JobProcessEvent($eventData));

        $fileName = 'vehicleQcExport-' . Str::uuid() . '.xlsx';
        $path = '/public/exports/' . $fileName;
        Excel::store(new VehicleQcReportExport($rows, $eventData), $path);
        return asset('/storage/exports/' . $fileName);

    }
}
