<?php

namespace App\Services\CustomerFinance;

use App\Services\Erp\ErpWsdlClient;

class PooyaCustomerFinanceService
{

    public function process($data): array
    {
        return ErpWsdlClient::run(
            'http://172.16.0.17:7047/ArianMotors/WS/POYA/Codeunit/API_Web_Online_Sales_call_API',
            'CustomerFinanceIsReady',
            [
                'customerFinanceNoP' => $data['reference'],
                'uRLAddressP'        => url('/api/sb/p/cf/'.$data['reference']),
                'goldenCardP'        => $data['golden_warranty'],
            ]);
    }
}
