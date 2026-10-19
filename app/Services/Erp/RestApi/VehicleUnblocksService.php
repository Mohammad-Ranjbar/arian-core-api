<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class VehicleUnblocksService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $barcodeInfo, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->send('VehicleUnblocks', ['BarcodeInfo' => $barcodeInfo], $model, 'unblock-vehicle', $userId);
    }
}
