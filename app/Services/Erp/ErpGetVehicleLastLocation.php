<?php

namespace App\Services\Erp;

use Illuminate\Validation\ValidationException;

readonly class ErpGetVehicleLastLocation
{
    public function __construct(private ErpOdataClient $client)
    {
    }

    public function get(string $vin, string $carModelReference): array
    {
        $vin = str_replace("'", "''", trim($vin));
        $carModelReference = str_replace("'", "''", trim($carModelReference));
        $url = config('erp.last_location') . '?$filter=' . rawurlencode(
                "Serial_No eq '$vin' and Item_No eq '$carModelReference'"
            );

        $result = $this->client->getOne($url);

        if (!$result) {
            throw ValidationException::withMessages([
                'message' => "تولید این خودرو ثبت نشده است",
                'color' => "green",
            ]);
        }

        return $this->getResult($result);
    }

    private function getResult($result): array
    {
        return [
            'last_location' => $result->Location_Code,
            'color' => 'green',
        ];
    }
}
