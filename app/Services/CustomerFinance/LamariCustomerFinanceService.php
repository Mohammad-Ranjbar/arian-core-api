<?php

namespace App\Services\CustomerFinance;

use App\Services\Erp\ErpWsdlClient;

class LamariCustomerFinanceService
{

    public function process($data): array
    {
        $url = url('/sb/l/cf/'.$data['reference']);
        if (isset($data['target']) && $data['target'] == 'PARS_AM') {
            $url = url('/sb/p/cf/'.$data['reference']);
        }
        return ErpWsdlClient::run(
            'http://172.16.0.17:7047/ArianTurboveh/WS/Vehicle%20LAMARI/Codeunit/API_Web_Online_Sales_call_API',
            'CustomerFinanceIsReady',
            [
                'customerFinanceNoP' => $data['reference'],
                'uRLAddressP'        => $url,
                'goldenCardP'        => $data['golden_warranty'],
            ]);
    }
}
