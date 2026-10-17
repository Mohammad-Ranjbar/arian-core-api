<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class GroupReturnsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vinArray, string $destination, string|int $numberVin, string|int $itemType, ?Model $model = null): array
    {
        return $this->jobs->send('GroupReturns', $this->payload($vinArray, $destination, $numberVin, $itemType), $model, 'gate-permit-group-return-request');
    }

    public function queue(string $vinArray, string $destination, string|int $numberVin, string|int $itemType, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->enqueue('GroupReturns', $this->payload($vinArray, $destination, $numberVin, $itemType), $model, 'gate-permit-group-return-request', $userId);
    }

    private function payload(string $vinArray, string $destination, string|int $numberVin, string|int $itemType): array
    {
        return [
            'VINArray' => $vinArray,
            'Destination' => $destination,
            'NumberVIN' => (string) $numberVin,
            'ItemTypeP' => (string) $itemType,
        ];
    }
}
