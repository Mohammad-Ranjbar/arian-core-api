<?php

namespace App\Services\Erp\RestApi;

use App\Services\Erp\RestApi\Servers\Server;

readonly class BasketsService
{
    public function __construct(private Server $server) {}

    public function get(string $barcodeInfo): array
    {
        if (trim($barcodeInfo) === '') {
            return [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'وارد کردن بارکد سبد الزامی است.',
                ],
                'http_status' => 422,
            ];
        }

        return $this->server->get('Baskets', body: ['BarcodeInfo' => $barcodeInfo]);
    }
}
