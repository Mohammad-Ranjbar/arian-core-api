<?php

namespace App\Services\Erp;

class ErpReturnRequest{
    public static function returnRequest(string $vin, string $locationCode): array
    {
        return ErpWsdlClient::run(config('erp.return_request'), 'ReturnRequest',
            ['vINP' => $vin, 'destinationP' => $locationCode]);

    }

    public static function returnBatchRequest(array $vin, string $locationCode): array
    {
        $vins = implode(',',$vin);
        $count = count($vin);
        return ErpWsdlClient::run(config('erp.return_request'), 'GroupReturnRequest',
            ['vINArrayP' => $vins, 'destinationP' => $locationCode,'numberVINP' => $count]);

    }
}
