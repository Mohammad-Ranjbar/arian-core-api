<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Http;

class SalesBridgeController extends Controller
{

    public function getOrderIds($prefix)
    {
        return Http::withoutVerifying()->get($this->getOrderUrl($prefix))->json();
    }

    public function getOrderDetail($prefix, $id)
    {
        return Http::withoutVerifying()->get($this->getOrderUrl($prefix).'/'.$id)->json();
    }

    public function getCustomerFinance($prefix,  $invoiceReference)
    {
        return Http::withoutVerifying()->get($this->getUrl($prefix).'/customer-finances/'.$invoiceReference)->json();
    }

    public function getSecondPayment($prefix,  $id)
    {
        return Http::withoutVerifying()->get($this->getUrl($prefix).'/second-payment/'.$id)->json();
    }

    public function getOrderUrl($prefix)
    {
        $link1 = 'https://sales.lamari.ir/api/report-orders-sec-by-3f88895bdffcca0a01ef6c722a35b591D';
        $link2 = 'https://sales.arian-motor.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D-PARS_AM';


        $urls = [
            'l' => $link1,
            'd' => 'https://sales.arian-diesel.com/api/report-orders-sec-by-3f88895bdffcca0a01ef6c722a35b591D',
            'p' => 'https://sales.arian-motor.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D-POOYA_AM',
            'a' => 'http://sales.arinadrive.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D-KIA_AD',
            'test' => 'http://sales.lamari.ir/api/check-test-orders',
        ];

        return $urls[$prefix];
    }


    public function getUrl($prefix)
    {
        $urls = [
            'l' => 'https://sales.lamari.ir/api/report-orders-sec-by-3f88895bdffcca0a01ef6c722a35b591D',
            'd' => 'https://sales.arian-diesel.com/api/report-orders-sec-by-3f88895bdffcca0a01ef6c722a35b591D',
            'p' => 'https://sales.arian-motor.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D',
            'a' => 'http://sales.arinadrive.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D',
            'test' => 'http://sales.lamari.ir/api/check-test-orders',
        ];

        return $urls[$prefix];
    }

}













