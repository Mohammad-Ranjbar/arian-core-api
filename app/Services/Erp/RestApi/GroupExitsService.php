<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class GroupExitsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(string $vinArray, string|int $typeOfExit, string $destination, string|int $numberVin, string|int $itemType, ?Model $model = null): array
    {
        return $this->jobs->send('GroupExits', $this->payload($vinArray, $typeOfExit, $destination, $numberVin, $itemType), $model, 'gate-permit-group-exit-request');
    }

    public function queue(string $vinArray, string|int $typeOfExit, string $destination, string|int $numberVin, string|int $itemType, ?Model $model = null, ?int $userId = null): array
    {
        return $this->jobs->enqueue('GroupExits', $this->payload($vinArray, $typeOfExit, $destination, $numberVin, $itemType), $model, 'gate-permit-group-exit-request', $userId);
    }

    private function payload(string $vinArray, string|int $typeOfExit, string $destination, string|int $numberVin, string|int $itemType): array
    {
        return [
            'VINArray' => $vinArray,
            'TypeOfExit' => (string) $typeOfExit,
            'Destination' => $destination,
            'NumberVIN' => (string) $numberVin,
            'ItemType' => (string) $itemType,
        ];
    }
}
