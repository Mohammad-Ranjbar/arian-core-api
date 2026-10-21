<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ExitRequestsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vin, string|int $typeOfExit, string $destination, string|int $itemType, ?Model $model = null, ?int $userId = null): PendingRestErpRequest
    {
        return $this->jobs->pending('ExitRequests', $this->payload($vin, $typeOfExit, $destination, $itemType), $model, 'درخواست خروج خودرو', $userId);
    }

    public function dispatch(string $vin, string|int $typeOfExit, string $destination, string|int $itemType, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('ExitRequests', $this->payload($vin, $typeOfExit, $destination, $itemType), $model, 'درخواست خروج خودرو', $userId, mode: $mode);
    }

    public function queue(string $vin, string|int $typeOfExit, string $destination, string|int $itemType, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->enqueue('ExitRequests', $this->payload($vin, $typeOfExit, $destination, $itemType), $model, 'درخواست خروج خودرو', $userId);
    }

    private function payload(string $vin, string|int $typeOfExit, string $destination, string|int $itemType): array
    {
        return [
            'VIN' => $vin,
            'TypeOfExit' => (string) $typeOfExit,
            'Destination' => $destination,
            'ItemType' => (string) $itemType,
        ];
    }
}
