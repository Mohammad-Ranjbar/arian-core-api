<?php

namespace App\Services\CustomerFinance;

use App\Services\Erp\ErpWsdlClient;

class DieselCustomerFinanceService
{

    public function process($data): array
    {
        $url = 'http://172.16.0.17:7047/ArianVeh/WS/ArianPars/Codeunit/OnlineSales';
         return ErpWsdlClient::run($url,'CustomerFinanceIsReady',[
            'customerFinanceNoP' => $data['reference'],
             'uRLAddressP' => 'http://172.16.0.130:8080/api/sb/d/cf/'.$data['reference'],
            'goldenCardP' => $data['golden_warranty'],
        ]);
    }
}
