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
        return $this->getResult($result);
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

    /**
     * @throws ValidationException
     */
    public function getByPo($po, bool $throw = true): array|bool
    {
        $po = trim($po, '* ');
        $url = config('erp.vehicles') . '?$filter=Production_Order eq \'' . $po . '\'';
        $result = $this->client->getOne($url);

        if (!$result) {
            Log::info('get PO: ' . $po . 'by User:' . Auth::id() . ' not found');
            if ($throw) {
                throw ValidationException::withMessages(['message' => 'vehicle not found']);
            }
            return false;
        }
        return $this->getResult($result);
    }

    /**
     * @throws ValidationException
     */
    public function getByPoFromHeader($po): array
    {
        $po = trim($po, '* ');
        $url = config('erp.production_header') . '?$filter=No eq \'' . $po . '\'';
        $result = $this->client->getOne($url);

        if (!$result) {
            Log::info('get PO: ' . $po . 'by User:' . Auth::id() . ' not found');

            throw ValidationException::withMessages(['message' => 'vehicle not found']);

        }
        return $this->getResult2($result);
    }

    private function getResult($result): array
    {
        $data = [
            'vin' => $result->VIN,
            'color_code' => $result->ColorCode,
            'production_order_number' => $result->ProductionOrder,
            'engine_no' => $result->EngineNo,
            'cabin_no' => $result->CabinNo,
            'make_code' => $this->getMakeCode($result->MakeCode),
            'car_model' => $this->getCarModel($result->ModelNo),
            'is_blocked' => $result->Blocked,
            'data' => $result
        ];

        return $data;
    }
    private function getResult2($result): array
    {
        return [
            'vin' => 'temp-'.$result->No,
            'color_code' => $result->VariantCode,
            'production_order_number' => $result->No,
            'engine_no' => '',
            'cabin_no' => '',
            'make_code' => $this->getMakeCode($result->Shortcut_Dimension_2_Code),
            'car_model' => $this->getCarModel($result->Source_No),
            'is_blocked' => false,
            'data' => $result,
        ];
    }


}
