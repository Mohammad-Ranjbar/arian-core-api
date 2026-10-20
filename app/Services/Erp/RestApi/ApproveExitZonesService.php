<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ApproveExitZonesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function dispatch(string $barcodeInfo, string|int $itemType, ?Model $model = null, ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('ApproveExitZones', $this->payload($barcodeInfo, $itemType), $model, 'تأیید خروج خودرو', userId: $userId, mode: $mode);
    }

    public function send(string $barcodeInfo, string|int $itemType, ?Model $model = null): PendingRestErpRequest
    {
        return $this->jobs->pending('ApproveExitZones', $this->payload($barcodeInfo, $itemType), $model, 'تأیید خروج خودرو');
    }

    private function payload(string $barcodeInfo, string|int $itemType): array
    {
        return [
            'BarcodeInfo' => $barcodeInfo,
            'ItemType' => (string) $itemType,
        ];
    }
}
