<?php


namespace App\Services\Erp;


use Exception;

class ErpCreateExitRequest {
    /**
     * @throws Exception
     */
    public static function createExitRequest(string $vin, string $type, string $locationCode): array
    {

       $exitRequest = ErpWsdlClient::run(config('erp.exit_request'), 'CreateExitRequest',
            ['vINP' => $vin, 'typeOfExitP' => $type, 'destinationP' => $locationCode]);

        if ($exitRequest['status'] !== 'ok') {
            throw new Exception($exitRequest['message']);
        }

        return   ErpWsdlClient::run(config('erp.print'), 'PrintExitRequest', [
            'vINP' => $vin
        ]);

    }

    /**
     * @throws Exception
     */
    public static function createBatchExitRequest(array $vin, string $type, string $locationCode,$itemType): array
    {
        $vins = implode(',',$vin);
        $count = count($vin);
        $exitRequest = ErpWsdlClient::run(config('erp.exit_request'), 'GroupExitRequest',
            ['vINArrayP' => $vins, 'typeOfExitP' => $type, 'destinationP' => $locationCode,'numberVINP' => $count,'itemTypeP' => $itemType]);

        if ($exitRequest['status'] !== 'ok') {
            throw new Exception($exitRequest['message']);
        }

        return   ErpWsdlClient::run(config('erp.print'), 'PrintExitRequest', [
            'vINP' => $vin
        ]);

    }
}
