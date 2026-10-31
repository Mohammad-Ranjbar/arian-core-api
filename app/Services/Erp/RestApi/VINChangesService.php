<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class VINChangesService
{
    private const string SECTION = 'تغییر شماره شاسی خودرو';

    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $productionNo, string $tempVin, string $vin, ?Model $model = null, ?int $userId = null): PendingRestErpRequest
    {
        return $this->jobs->pending('VINChanges', $this->payload($productionNo, $tempVin, $vin), $model, self::SECTION, $userId);
    }

    public function dispatch(string $productionNo, string $tempVin, string $vin, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('VINChanges', $this->payload($productionNo, $tempVin, $vin), $model, self::SECTION, $userId, mode: $mode);
    }

    public function queue(string $productionNo, string $tempVin, string $vin, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->enqueue('VINChanges', $this->payload($productionNo, $tempVin, $vin), $model, self::SECTION, $userId);
    }

    private function payload(string $productionNo, string $tempVin, string $vin): array
    {
        return ['ProductionNo' => $productionNo, 'TempVIN' => $tempVin, 'VIN' => $vin];
    }
}
