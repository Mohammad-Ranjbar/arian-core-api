<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class CreateSynchroensService
{
    private const SECTION = 'ارسال سنکرون قطعات به ERP';

    public function __construct(private RestErpJobService $jobs) {}

    public function send(
        string $crossReference,
        string $doc,
        string $locationCode,
        string $newLocationCode,
        string $variantCode,
        string|int $number,
        string $batchNo,
        ?Model $model = null,
        ?int $userId = null
    ): PendingRestErpRequest {
        return $this->jobs->pending('CreateSynchroens', $this->payload($crossReference, $doc, $locationCode, $newLocationCode, $variantCode, $number, $batchNo), $model, self::SECTION, $userId);
    }

    public function dispatch(
        string $crossReference,
        string $doc,
        string $locationCode,
        string $newLocationCode,
        string $variantCode,
        string|int $number,
        string $batchNo,
        ?Model $model = null,
        ?int $userId = null,
        ?RestErpExecutionMode $mode = null
    ): array {
        return $this->jobs->dispatch('CreateSynchroens', $this->payload($crossReference, $doc, $locationCode, $newLocationCode, $variantCode, $number, $batchNo), $model, self::SECTION, $userId, mode: $mode);
    }

    public function queue(
        string $crossReference,
        string $doc,
        string $locationCode,
        string $newLocationCode,
        string $variantCode,
        string|int $number,
        string $batchNo,
        ?Model $model = null,
        ?int $userId = null
    ): array {
        return $this->jobs->enqueue('CreateSynchroens', $this->payload($crossReference, $doc, $locationCode, $newLocationCode, $variantCode, $number, $batchNo), $model, self::SECTION, $userId);
    }

    private function payload(
        string $crossReference,
        string $doc,
        string $locationCode,
        string $newLocationCode,
        string $variantCode,
        string|int $number,
        string $batchNo
    ): array {
        return [
            'CrossReference' => $crossReference,
            'Doc' => $doc,
            'LocationCode' => $locationCode,
            'NewLocationCode' => $newLocationCode,
            'VariantCode' => $variantCode,
            'Number' => (string) $number,
            'BatchNo' => $batchNo,
        ];
    }
}
