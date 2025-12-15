<?php

namespace App\Services\CustomerFinance;

use App\Services\Erp\ErpWsdlClient;

class ArinaCustomerFinanceService
{

    public function process($data): array
    {
        $url = 'http://172.16.0.17:7047/ArianMotors/WS/Kia/Codeunit/API_Web_Online_Sales_call_API';
        return ErpWsdlClient::run($url, 'CustomerFinanceIsReady', [
            'customerFinanceNoP' => $data['reference'],
            'uRLAddressP'        => 'http://172.16.0.130:8080/api/sb/a/cf/'.$data['reference'],
            'goldenCardP'        => $data['golden_warranty'],
        ]);
    }
}
