<?php

namespace App\Services\Erp;

class ErpReturnRequest{
    public static function returnRequest(string $vin, string $locationCode): array
    {
        return ErpWsdlClient::run(config('erp.return_request'), 'ReturnRequest',
            ['vINP' => $vin, 'destinationP' => $locationCode]);

    }
}
