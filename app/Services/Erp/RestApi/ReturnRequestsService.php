<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ReturnRequestsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vin, string $destination, string|int $itemType, ?Model $model = null): PendingRestErpRequest
    {
        return $this->jobs->pending('ReturnRequests', $this->payload($vin, $destination, $itemType), $model, 'درخواست بازگشت خودرو');
    }

    public function sendOrRetry(string $vin, string $destination, string|int $itemType, ?int $userId = null): array
    {
        return $this->jobs->send(
            'ReturnRequests', $this->payload($vin, $destination, $itemType),
            section: 'درخواست بازگشت خودرو', userId: $userId, reuseExistingJob: true
        );
    }

    public function dispatch(string $vin, string $destination, string|int $itemType, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('ReturnRequests', $this->payload($vin, $destination, $itemType), $model, 'درخواست بازگشت خودرو', $userId, mode: $mode);
    }

    public function queue(string $vin, string $destination, string|int $itemType, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->enqueue('ReturnRequests', $this->payload($vin, $destination, $itemType), $model, 'درخواست بازگشت خودرو', $userId);
    }

    private function payload(string $vin, string $destination, string|int $itemType): array
    {
        return [
            'VIN' => $vin,
            'Destination' => $destination,
            'ItemType' => (string) $itemType,
        ];
    }
}
