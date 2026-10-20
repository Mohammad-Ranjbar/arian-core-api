<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class TrackingsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function dispatch(string $vin, string $barcode, string|int $itemType, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('Trackings', $this->payload($vin, $barcode, $itemType), $model, 'ثبت بارکد قطعات خودرو', userId: $userId, mode: $mode);
    }

    public function send(string $vin, string $barcode, string|int $itemType, ?Model $model = null): PendingRestErpRequest
    {
        return $this->jobs->pending('Trackings', $this->payload($vin, $barcode, $itemType), $model, 'ثبت بارکد قطعات خودرو');
    }

    private function payload(string $vin, string $barcode, string|int $itemType): array
    {
        return [
            'VIN' => $vin,
            'BarCode' => $barcode,
            'ItemType' => (string) $itemType,
        ];
    }
}
