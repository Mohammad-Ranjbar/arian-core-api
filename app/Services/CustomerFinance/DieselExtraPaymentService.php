<?php

namespace App\Services\CustomerFinance;

use App\Services\Erp\ErpWsdlClient;

class DieselExtraPaymentService
{

    public function process($id): array
    {
        $url = 'http://172.16.0.17:7047/ArianVeh/WS/ArianPars/Codeunit/OnlineSales';
         return ErpWsdlClient::run($url,'SecondPaymentIsReady',[
            'onlineContractIDP' => $id
        ]);
    }
}
