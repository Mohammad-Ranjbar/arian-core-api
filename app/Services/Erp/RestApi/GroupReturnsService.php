<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class GroupReturnsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vinArray, string $destination, string|int $numberVin, string|int $itemType, ?Model $model = null, ?int $userId = null): PendingRestErpRequest
    {
        return $this->jobs->pending('GroupReturns', $this->payload($vinArray, $destination, $numberVin, $itemType), $model, 'درخواست بازگشت گروهی خودروها', $userId);
    }

    public function dispatch(string $vinArray, string $destination, string|int $numberVin, string|int $itemType, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('GroupReturns', $this->payload($vinArray, $destination, $numberVin, $itemType), $model, 'درخواست بازگشت گروهی خودروها', $userId, mode: $mode);
    }

    public function queue(string $vinArray, string $destination, string|int $numberVin, string|int $itemType, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->enqueue('GroupReturns', $this->payload($vinArray, $destination, $numberVin, $itemType), $model, 'درخواست بازگشت گروهی خودروها', $userId);
    }

    private function payload(string $vinArray, string $destination, string|int $numberVin, string|int $itemType): array
    {
        return [
            'VINArray' => $vinArray,
            'Destination' => $destination,
            'NumberVIN' => (string) $numberVin,
            'ItemTypeP' => (string) $itemType,
        ];
    }
}
