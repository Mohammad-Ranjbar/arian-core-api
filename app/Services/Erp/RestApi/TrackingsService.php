<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class TrackingsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vin, string $barcode, string|int $itemType, ?Model $model = null): array
    {
        return $this->jobs->send('Trackings', [
            'VIN' => $vin,
            'BarCode' => $barcode,
            'ItemType' => (string) $itemType,
        ], $model, 'serial-barcode');
    }
}
