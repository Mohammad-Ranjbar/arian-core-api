<?php

namespace App\Services\Erp;


use Exception;
use Illuminate\Support\Facades\Log;

class ErpGetVehicle
{

    public function __construct(private ErpOdataClient $client)
    {
    }

    public function get($vin)
    {
        try {
            $vin = trim($vin, '* ');
            $url = config('erp.vehicles').'?$filter=VIN eq \''.$vin.'\'';
            $result = $this->client->getOne($url);
            if (!$result) {
                return response()->json(['message' => 'invalid-vin'], 404);
            }
            return [
                'vin'                     => $result->VIN,
                'production_order_number' => $result->Production_Order,
                'engine_no'               => $result->Engine_No,
                'cabin_no'                => $result->Cabin_No,
                'make_code'               => $this->getMakeCode($result->Make_Code),
                'car_model'               => $this->getCarModel($result->Model_No),
                'is_blocked'              => $result->Blocked,
            ];
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    private function getMakeCode($makeCode): ?array
    {
        $url = config('erp.make_codes').'?$filter=Code eq \''.$makeCode.'\'';
        $result = $this->client->getOne($url);
        return $result ? [
            'reference' => $result->Code,
            'title'     => $result->Code
        ] : null;
    }

    private function getCarModel($modelNo): ?array
    {
        $url = config('erp.items').'?$filter=No eq \''.$modelNo.'\'&Item_Type eq \'vehicle\'';
        $result = $this->client->getOne($url);
        return $result ? [
            'reference' => $result->No,
            'title'     => $result->No
        ] : null;
    }
}
