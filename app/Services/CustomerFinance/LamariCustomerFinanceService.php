<?php

namespace App\Services\CustomerFinance;

use App\Services\Erp\ErpWsdlClient;

class LamariCustomerFinanceService
{

    public function process($data): array
    {
        $url = 'http://172.16.0.17:7047/ArianTurboveh/WS/Vehicle%20LAMARI/Codeunit/API_Web_Online_Sales_call_API';
         return ErpWsdlClient::run($url,'CustomerFinanceIsReady',[
            'customerFinanceNoP' => $data['reference'],
             'uRLAddressP' => url('/sb/l/cf/'.$data['reference']),
            'goldenCardP' => $data['golden_warranty'],
        ]);
    }
}
