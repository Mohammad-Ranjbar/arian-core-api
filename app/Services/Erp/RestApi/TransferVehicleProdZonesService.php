<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class TransferVehicleProdZonesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vin, string $toLocationType, ?Model $model = null): array
    {
        return $this->jobs->send('TransferVehicleProdZones', [
            'VIN' => $vin,
            'ToLocationType' => $toLocationType,
        ], $model, 'انتقال خودرو به محل فروش');
    }
}
