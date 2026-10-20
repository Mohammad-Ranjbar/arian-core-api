<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ImportVehiclesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function dispatch(string $vin, array $data, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('ImportVehicles', $this->payload($vin, $data), $model, 'ثبت نتیجه بازرسی پیش از تحویل خودرو', $userId, mode: $mode);
    }

    public function send(string $vin, array $data, ?Model $model = null, ?int $userId = null): PendingRestErpRequest
    {
        return $this->jobs->pending('ImportVehicles', $this->payload($vin, $data), $model, 'ثبت نتیجه بازرسی پیش از تحویل خودرو', $userId);
    }

    private function payload(string $vin, array $data): array
    {
        return [
            'VIN' => $vin,
            'PDIResult' => (string) ($data['pdi_result'] ?? ''),
            'PDIDescription' => (string) ($data['pdi_result_description'] ?? ''),
            'PDIResultDesc' => (string) ($data['pdi_result_description_2'] ?? ''),
            'EngineNo' => (string) ($data['engine_no'] ?? ''),
            'ModelYear' => (string) ($data['model_year'] ?? ''),
            'PoliceInspectionDate' => (string) ($data['police_inspection_date'] ?? ''),
        ];
    }
}
