<?php

namespace App\Services\CustomerFinance;

use App\Services\Erp\ErpWsdlClient;

class ArinaExtraPaymentService
{

    public function process($id): array
    {
        $url = 'http://172.16.0.17:7047/ArianMotors/WS/Kia/Codeunit/API_Web_Online_Sales_call_API';
         return ErpWsdlClient::run($url,'SecondPaymentIsReady',[
            'onlineContractIDP' => $id
        ]);
    }
}
