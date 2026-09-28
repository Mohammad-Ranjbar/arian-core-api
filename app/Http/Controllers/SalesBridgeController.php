<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SalesBridgeController extends Controller
{

    const string FROM_DATE = '2026-08-21 00:00:00';

    public function getOrderIds($prefix)
    {
        return Http::withoutVerifying()->get($this->getOrderUrl($prefix))->json();
    }

    public function getOrderUrl($prefix)
    {
        $link1 = 'https://sales.lamari.ir/api/report-orders-sec-by-3f88895bdffcca0a01ef6c722a35b591D';

        $link2 = 'https://sales.arian-motor.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D-PARS_AM';
        $link3 = 'https://sales.arinadrive.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D-PARS_AD';


        $urls = [
            'l'    => $link3,
            'd'    => 'https://sales.arian-diesel.com/api/report-orders-sec-by-3f88895bdffcca0a01ef6c722a35b591D',
            'p'    => 'https://sales.arian-motor.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D-POOYA_AM',
            'a'    => 'https://sales.arinadrive.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D-KIA_AD',
            'test' => 'https://sales.lamari.ir/api/check-test-orders',
        ];

        return $urls[$prefix];
    }

    public function getOrderDetail($prefix, $id)
    {
        $data = Http::withoutVerifying()->get($this->getOrderUrl($prefix).'/'.$id)->json();
        if (isset($data['order']['payments'])) {
            if (isset($data['order']['payments']['items'])) {
                $data['order']['payments']['items'] = array_map(function ($payment) {
                    return $this->managePaymentDate($payment);
                }, $data['order']['payments']['items']);
            }
        }
        return $data;
    }

    private function managePaymentDate($payment)
    {
        $date = Carbon::make($payment['created_at']);
        if ($date->greaterThan(self::FROM_DATE)) {
            $date->addDay();
            $payment['created_at'] = $date->format('Y-m-d H:i:s');
            $payment['created_at_date'] = $date->format('Y-m-d');
        }
        if (isset($payment['bank_data'])) {
            if (isset($payment['bank_data']['date'])) {
                $date = Carbon::make($payment['bank_data']['date']['full']);
                if ($date->greaterThan(self::FROM_DATE)) {
                    $date->addDay();
                    $payment['bank_data']['date']['full'] = $date->format('Y-m-d H:i:s');
                    $payment['bank_data']['date']['date'] = $date->format('Y-m-d');
                }
            }
        }

        $payment['bank_data'] = [
            'RetrivalRefNo' => $payment['bank_data']['RetrivalRefNo'],
            'date'          => $payment['bank_data']['date'],
        ];

        return $payment;
    }

    public function getCustomerFinance($prefix, $invoiceReference)
    {
        $data = Http::withoutVerifying()->get($this->getUrl($prefix).'/customer-finances/'.$invoiceReference)->json();
        if (isset($data['result'])) {
            if (isset($data['result']['payments'])) {
                $data['result']['payments'] = array_map(function ($payment) {
                    return $this->managePaymentDate($payment);
                }, $data['result']['payments']);
            }
        }
        return $data;
    }

    public function getUrl($prefix)
    {
        $urls = [
            'l'    => 'https://sales.lamari.ir/api/report-orders-sec-by-3f88895bdffcca0a01ef6c722a35b591D',
            'd'    => 'https://sales.arian-diesel.com/api/report-orders-sec-by-3f88895bdffcca0a01ef6c722a35b591D',
            'p'    => 'https://sales.arian-motor.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D',
            'a'    => 'https://sales.arinadrive.com/api/report-orders-sec-by-13f88895bdffcca0a01ef6c722a35b591D',
            'test' => 'https://sales.lamari.ir/api/check-test-orders',
        ];

        return $urls[$prefix];
    }

    public function getSecondPayment($prefix, $id)
    {
        if ($prefix == 'l') {
            if (Str::contains($id, 'PARS_AD')) {
                $prefix = 'a';
            }
            if (Str::contains($id, 'PARS_AM')) {
                $prefix = 'p';
            }
        }
        $url = $this->getUrl($prefix).'/second-payment/'.$id;

        $data = Http::withoutVerifying()->get($url)->json();

        if (isset($data['result'])) {
            if (isset($data['result']['payments'])) {
                $data['result']['payments'] = array_map(function ($payment) {
                    return $this->managePaymentDate($payment);
                }, $data['result']['payments']);
            }
        }
        return $data;
    }

    public function getNationalCodes($prefix)
    {
        return Http::withoutVerifying()->get($this->getUrl($prefix).'/import/users')->json();
    }

    public function getMember($prefix, $nationalCode)
    {
        return Http::withoutVerifying()->get($this->getUrl($prefix).'/import/users/'.$nationalCode)->json();
    }

}
