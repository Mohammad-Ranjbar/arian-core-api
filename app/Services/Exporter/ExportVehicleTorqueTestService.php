<?php

namespace App\Services\Exporter;

use App\Events\JobProcessEvent;
use App\Exports\VehicleTorqueTestReportExport;
use App\Models\VehicleTorqueTest;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;

class ExportVehicleTorqueTestService extends BaseExporterService
{
    /**
     * @param $job
     */
    public function __construct(public $job)
    {

    }


    public function export(): false|string
    {
        try {
            $rows = VehicleTorqueTest::getReportData((array)$this->job->getAttribute('data'));
            $eventData = [
                'id' => $this->job->uuid,
                'user_id' => $this->job->user_id,
                'total' => $rows->count(),
                'done' => 0,
                'success' => 0,
                'error' => 0,
            ];
            event(new JobProcessEvent($eventData));

            $fileName = 'vehicleTorqueTest-' . Str::uuid() . '.xlsx';
            $path = '/public/exports/' . $fileName;

            Excel::store(new VehicleTorqueTestReportExport($rows, $eventData), $path);
            return $fileName;
        }catch (Exception $exception){
            logger()->error($exception->getMessage());
            return false;
        }

    }
}
