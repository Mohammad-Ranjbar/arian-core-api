<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Http\Controllers\PDIController;
use App\Http\Controllers\VehicleController;
use App\Models\Job;
use App\Models\PDI;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;


class PoliceImport implements ToCollection, WithStartRow, WithMapping
{

    public function __construct(public $data)
    {

    }

    public function map($row): array
    {
        $columns = [];
        foreach (PDI::HEADING_POLICE as $key => $value) {
            $columns[$value] = $row[$key];
        }
        return $columns;
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $collection): void
    {
        $this->data['total'] = $collection->count();
        $this->sendEvent();
        $userId = $this->data['user_id'];
        $vehicleController = app(VehicleController::class);
        $collection->each(function ($row) use ($userId, $vehicleController) {
            try {
                $vehicleController->getByVin($row['vin']);
                $data = PDI::query()->create([
                    'vin' => $row['vin'],
                    'police_inspection_date' => $row['police_inspection_date'],
                    'user_id' => $userId,
                ]);

                $response = PDIController::sendPDIToErp($row['vin'], $data->toArray());
                if ($response['status'] !== 'ok') {
                    throw new Exception($response['message'], 400);
                }
            } catch (Exception $exception) {
                $this->data['error']++;
                $row['result'] = $exception->getMessage();
            }

            $this->data['done']++;
            $this->sendEvent();
        });
        $uuid = Str::uuid();
        Excel::store(new ResultExport($collection->prepend(PDI::HEADING_POLICE)), 'public/results/' . $uuid . '.xlsx');
        Job::query()->find($this->data['id'])->update([
            'result' => [
                'file' => route('dl', ['path' => 'results/' . $uuid . '.xlsx']),
                'total' => $this->data['total'],
                'success' => $this->data['success'],
                'error' => $this->data['error'],
            ]
        ]);

    }

    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->data));
    }
}
