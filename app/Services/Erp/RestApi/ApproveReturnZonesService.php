<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ApproveReturnZonesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function dispatch(string $vin, string|int $itemType, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('ApproveReturnZones', $this->payload($vin, $itemType), $model, 'تأیید ورود خودرو', userId: $userId, mode: $mode);
    }

    public function send(string $vin, string|int $itemType, ?Model $model = null): PendingRestErpRequest
    {
        return $this->jobs->pending('ApproveReturnZones', $this->payload($vin, $itemType), $model, 'تأیید ورود خودرو');
    }

    private function payload(string $vin, string|int $itemType): array
    {
        return [
            'VIN' => $vin,
            'ItemType' => (string) $itemType,
        ];
    }
}
