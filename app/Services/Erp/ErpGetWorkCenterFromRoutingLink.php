<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpGetWorkCenterFromRoutingLink
{
    public function __construct(private readonly ErpOdataClient $client)
    {
    }

    public function get(string $carModelReference): array
    {
        $carModelReference = str_replace("'", "''", $carModelReference);
        $url = config('erp.work_center') . '?$filter=' . rawurlencode(
                "ItemNo eq '$carModelReference' and RoutingStatus eq 'Certified'"
            );

        $result = $this->client->get($url);

        if (!$result) {
            Log::info('get routing link code From car model reference: ' . $carModelReference);

            throw ValidationException::withMessages(['message' => 'Bom Item from' . $carModelReference . 'not found']);
        }
        return $this->getResult($result);
    }


    private function getResult($result): array
    {
        return collect($result->value)->map(fn($data) => [
            'work_center' => $data->No,
            'routing_link_code' => $data->RoutingLinkCode,
            'data' => $data,
        ])->values()->all();
    }


}
