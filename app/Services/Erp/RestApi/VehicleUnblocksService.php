<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class VehicleUnblocksService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function dispatch(string $barcodeInfo, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('VehicleUnblocks', ['BarcodeInfo' => $barcodeInfo], $model, 'رفع مسدودی خودرو', $userId, mode: $mode);
    }

    public function send(string $barcodeInfo, ?Model $model = null, ?int $userId = null): PendingRestErpRequest
    {
        return $this->jobs->pending('VehicleUnblocks', ['BarcodeInfo' => $barcodeInfo], $model, 'رفع مسدودی خودرو', $userId);
    }
}
