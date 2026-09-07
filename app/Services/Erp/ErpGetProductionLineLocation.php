<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpGetProductionLineLocation
{
    public function __construct(private readonly ErpOdataClient $client)
    {
    }

    public function get(string $carModelReference): array
    {
        $carModelReference = trim($carModelReference);

        $url = config('erp.production_line_location') . '?$filter=Item_No eq \'' . $carModelReference . '\'';

        $result = $this->client->get($url);

        if (!$result) {
            throw ValidationException::withMessages(['message' => 'locations for car model reference from' . $carModelReference . 'not found']);
        }

        return $this->getResult($result);
    }


    private function getResult($result): array
    {
        return collect($result->value)->map(fn($data) => [
            'location_code' => $data->Location_Code,

//            'data'              => $data,
        ])->values()->all();
    }


}
