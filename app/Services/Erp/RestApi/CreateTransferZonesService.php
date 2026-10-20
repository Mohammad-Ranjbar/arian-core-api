<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class CreateTransferZonesService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(
        string $itemNoArray,
        string $itemQtyArray,
        string|int $numberItems,
        string $fromLocationType,
        string $toLacationType,
        string $variantArray,
        string $crossRefArray,
        string $makeCode,
        string|int $documentNo,
        ?Model $model = null
    ): PendingRestErpRequest {
        return $this->jobs->pending('CreateTransferZones', $this->payload($itemNoArray, $itemQtyArray, $numberItems, $fromLocationType, $toLacationType, $variantArray, $crossRefArray, $makeCode, $documentNo), $model, 'ارسال حواله انتقال قطعات به تولید');
    }

    public function dispatch(
        string $itemNoArray,
        string $itemQtyArray,
        string|int $numberItems,
        string $fromLocationType,
        string $toLacationType,
        string $variantArray,
        string $crossRefArray,
        string $makeCode,
        string|int $documentNo,
        ?Model $model = null,
        ?int $userId = null, ?RestErpExecutionMode $mode = null): array
    {
        return $this->jobs->dispatch('CreateTransferZones', $this->payload($itemNoArray, $itemQtyArray, $numberItems, $fromLocationType, $toLacationType, $variantArray, $crossRefArray, $makeCode, $documentNo), $model, 'ارسال حواله انتقال قطعات به تولید', $userId, mode: $mode);
    }

    public function queue(
        string $itemNoArray,
        string $itemQtyArray,
        string|int $numberItems,
        string $fromLocationType,
        string $toLacationType,
        string $variantArray,
        string $crossRefArray,
        string $makeCode,
        string|int $documentNo,
        ?Model $model = null,
        ?int $userId = null
    ): array {
        return $this->jobs->enqueue('CreateTransferZones', $this->payload($itemNoArray, $itemQtyArray, $numberItems, $fromLocationType, $toLacationType, $variantArray, $crossRefArray, $makeCode, $documentNo), $model, 'ارسال حواله انتقال قطعات به تولید', $userId);
    }

    private function payload(
        string $itemNoArray,
        string $itemQtyArray,
        string|int $numberItems,
        string $fromLocationType,
        string $toLacationType,
        string $variantArray,
        string $crossRefArray,
        string $makeCode,
        string|int $documentNo
    ): array {
        return [
            'ItemNoArray' => $itemNoArray,
            'ItemQtyArray' => $itemQtyArray,
            'NumberItems' => (string) $numberItems,
            'FromLocationType' => $fromLocationType,
            'ToLacationType' => $toLacationType,
            'VariantArray' => $variantArray,
            'CrossRefArray' => $crossRefArray,
            'MakeCode' => $makeCode,
            'DocumentNo' => (string) $documentNo,
        ];
    }
}
