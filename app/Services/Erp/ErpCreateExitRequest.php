<?php


namespace App\Services\Erp;


class ErpCreateExitRequest {
    public static function createExitRequest(string $vin, string $type, string $locationCode): array
    {

       $exitRequest = ErpWsdlClient::run(config('erp.exit_request'), 'CreateExitRequest',
            ['vINP' => $vin, 'typeOfExitP' => $type, 'destinationP' => $locationCode]);

        if ($exitRequest['status'] !== 'ok') {
            throw new Exception($response['message']);
        }

        return   ErpWsdlClient::run(config('erp.print'), 'PrintExitRequest', [
            'vINP' => $vin
        ]);

    }
}
