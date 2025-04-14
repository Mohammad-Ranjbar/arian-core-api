<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpGetVehicle
{

    /**
     * @param ErpOdataClient $client
     */
    public function __construct(private readonly ErpOdataClient $client)
    {
    }


    /**
     * @param $vin
     * @return array
     * @throws ValidationException
     */
    public function get($vin): array
    {
        $vin = trim($vin, '* ');
        $url = config('erp.vehicles') . '?$filter=VIN eq \'' . $vin . '\'';
        $result = $this->client->getOne($url);

        if (!$result) {
            Log::info('get VIN: ' . $vin . 'by User:' . Auth::id() . ' not found');
            throw ValidationException::withMessages(['message' => 'vehicle not found']);
        }
        return [
            'vin' => $result->VIN,
            'color_code' => $result->Color_Code,
            'production_order_number' => $result->Production_Order,
            'engine_no' => $result->Engine_No,
            'cabin_no' => $result->Cabin_No,
            'make_code' => $this->getMakeCode($result->Make_Code),
            'car_model' => $this->getCarModel($result->Model_No),
            'is_blocked' => $result->Blocked,
            'data' => $result
        ];
    }

    /**
     * @param $makeCode
     * @return array|null
     */
    private function getMakeCode($makeCode): ?array
    {
        $url = config('erp.make_codes') . '?$filter=Code eq \'' . $makeCode . '\'';
        $result = $this->client->getOne($url);
        return $result ? [
            'reference' => $result->Code,
            'title' => $result->Code
        ] : null;
    }

    /**
     * @param $modelNo
     * @return array|null
     */
    private function getCarModel($modelNo): ?array
    {
        $url = config('erp.items') . '?$filter=No eq \'' . $modelNo . '\'';
        $result = $this->client->getOne($url);
        return $result ? [
            'reference' => $result->No,
            'title' => $result->No
        ] : null;
    }
}
