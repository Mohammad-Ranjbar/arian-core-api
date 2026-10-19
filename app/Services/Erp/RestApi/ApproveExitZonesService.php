<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ApproveExitZonesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $barcodeInfo, string|int $itemType, ?Model $model = null): array
    {
        return $this->jobs->send('ApproveExitZones', [
            'BarcodeInfo' => $barcodeInfo,
            'ItemType' => (string) $itemType,
        ], $model, 'تأیید خروج خودرو');
    }
}
