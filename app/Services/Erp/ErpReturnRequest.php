<?php

namespace App\Services\Erp;

class ErpReturnRequest
{
    public static function returnRequest(string $vin, string $locationCode,$itemType = null): array
    {
        return ErpWsdlClient::run(config('erp.return_request'), 'ReturnRequest',
            ['vINP' => $vin, 'destinationP' => $locationCode]);
//            ['vINP' => $vin, 'destinationP' => $locationCode,'itemTypeP' => $itemType]);

    }

    public static function returnBatchRequest(array $vin, string $locationCode, $itemType=null): array
    {
        $vins = implode(',', $vin);
        $count = count($vin);
        return ErpWsdlClient::run(config('erp.return_request'), 'GroupReturnRequest',
            ['vINArrayP' => $vins, 'destinationP' => $locationCode, 'numberVINP' => $count]);
//            ['vINArrayP' => $vins, 'destinationP' => $locationCode, 'numberVINP' => $count, 'itemTypeP' => $itemType]);

    }
}
