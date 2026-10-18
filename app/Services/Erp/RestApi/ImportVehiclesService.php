<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ImportVehiclesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vin, array $data, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->send('ImportVehicles', [
            'VIN' => $vin,
            'PDIResult' => (string) ($data['pdi_result'] ?? ''),
            'PDIDescription' => (string) ($data['pdi_result_description'] ?? ''),
            'PDIResultDesc' => (string) ($data['pdi_result_description_2'] ?? ''),
            'EngineNo' => (string) ($data['engine_no'] ?? ''),
            'ModelYear' => (string) ($data['model_year'] ?? ''),
            'PoliceInspectionDate' => (string) ($data['police_inspection_date'] ?? ''),
        ], $model, 'pdi', $userId);
    }
}
