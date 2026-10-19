<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class SetQcStatusService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(
        string $vin,
        string|int $qcStatus,
        string|int $itemType,
        string|int $qcType,
        ?Model $model = null
    ): array {
        return $this->jobs->send('qcStatuses', $this->payload($vin, $qcStatus, $itemType, $qcType), $model, 'ثبت وضعیت کنترل کیفیت خودرو');
    }

    public function queue(
        string $vin,
        string|int $qcStatus,
        string|int $itemType,
        string|int $qcType,
        ?Model $model = null,
        ?int $userId = null
    ): array {
        return $this->jobs->enqueue('qcStatuses', $this->payload($vin, $qcStatus, $itemType, $qcType), $model, 'ثبت وضعیت کنترل کیفیت خودرو', $userId);
    }

    private function payload(
        string $vin,
        string|int $qcStatus,
        string|int $itemType,
        string|int $qcType
    ): array {
        return [
            'VIN' => $vin,
            'QCStatus' => (string) $qcStatus,
            'ItemType' => (string) $itemType,
            'QCType' => (string) $qcType,
        ];
    }
}
