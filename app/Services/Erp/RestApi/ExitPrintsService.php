<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ExitPrintsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vin, string|int $itemType, ?Model $model = null, ?int $userId = null, ?int $dependsOnId = null): PendingRestErpRequest
    {
        return $this->jobs->pending('ExitPrints', $this->payload($vin, $itemType), $model, 'چاپ مجوز خروج خودرو', $userId, $dependsOnId);
    }

    public function dispatch(string $vin, string|int $itemType, ?Model $model = null, ?int $userId = null, ?int $dependsOnId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('ExitPrints', $this->payload($vin, $itemType), $model, 'چاپ مجوز خروج خودرو', $userId, $dependsOnId, mode: $mode);
    }

    public function queue(string $vin, string|int $itemType, ?Model $model = null, ?int $userId = null, ?int $dependsOnId = null): array
    {
        return $this->jobs->enqueue('ExitPrints', $this->payload($vin, $itemType), $model, 'چاپ مجوز خروج خودرو', $userId, $dependsOnId);
    }

    private function payload(string $vin, string|int $itemType): array
    {
        return ['VIN' => $vin, 'ItemType' => (string) $itemType];
    }
}
