<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ApproveReturnZonesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vin, string|int $itemType, ?Model $model = null): array
    {
        return $this->jobs->send('ApproveReturnZones', [
            'VIN' => $vin,
            'ItemType' => (string) $itemType,
        ], $model, 'تأیید ورود خودرو');
    }
}
