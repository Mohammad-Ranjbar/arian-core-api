<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class VehicleTypeBlockingsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $barcodeInfo, string $blockType, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->send('VehicleTypeBlockings', $this->payload($barcodeInfo, $blockType), $model, 'مسدودسازی خودرو', $userId);
    }

    public function queue(string $barcodeInfo, string $blockType, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->enqueue('VehicleTypeBlockings', $this->payload($barcodeInfo, $blockType), $model, 'مسدودسازی خودرو', $userId);
    }

    private function payload(string $barcodeInfo, string $blockType): array
    {
        return ['BarcodeInfo' => $barcodeInfo, 'BlockType' => $blockType];
    }
}
