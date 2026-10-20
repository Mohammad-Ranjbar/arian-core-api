<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ProductionPostingBINsService
{
    private const SECTION = 'ثبت عملیات تولید خودرو';

    public function __construct(private RestErpJobService $jobs) {}

    public function dispatch(string $vin, string $wcGroup, string $withTracking, string $withOutput, string|int $itemType, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('ProductionPostingBINs', $this->payload($vin, $wcGroup, $withTracking, $withOutput, $itemType), $model, self::SECTION, userId: $userId, mode: $mode);
    }

    public function send(string $vin, string $wcGroup, string $withTracking, string $withOutput, string|int $itemType, ?Model $model = null): PendingRestErpRequest
    {
        return $this->jobs->pending('ProductionPostingBINs', $this->payload($vin, $wcGroup, $withTracking, $withOutput, $itemType), $model, self::SECTION);
    }

    private function payload(string $vin, string $wcGroup, string $withTracking, string $withOutput, string|int $itemType): array
    {
        return [
            'VIN' => $vin,
            'WCGroup' => $wcGroup,
            'WithTracking' => $withTracking,
            'WithOutput' => $withOutput,
            'ItemType' => (string) $itemType,
        ];
    }
}
