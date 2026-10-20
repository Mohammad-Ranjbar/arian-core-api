<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class TransferVehicleProdZonesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function dispatch(string $vin, string $toLocationType, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('TransferVehicleProdZones', $this->payload($vin, $toLocationType), $model, 'انتقال خودرو به محل فروش', userId: $userId, mode: $mode);
    }

    public function send(string $vin, string $toLocationType, ?Model $model = null): PendingRestErpRequest
    {
        return $this->jobs->pending('TransferVehicleProdZones', $this->payload($vin, $toLocationType), $model, 'انتقال خودرو به محل فروش');
    }

    private function payload(string $vin, string $toLocationType): array
    {
        return [
            'VIN' => $vin,
            'ToLocationType' => $toLocationType,
        ];
    }
}
