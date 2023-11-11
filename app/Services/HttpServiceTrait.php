<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


trait HttpServiceTrait
{
    public function getVehicleByVin($vin)
    {
        return $this->sendToErp('/vehicle/'.$vin);
    }

    final  public function sendToErp($path, array $params = [], $method = 'get')
    {
        try {
            $res = Http::$method(config('httpService.url').$path, array_merge($params, [
                'token' => config('httpService.token')
            ]));
            return $res->json();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return false;
        }
    }

    public function sendSms($number, $content)
    {
        return $this->sendToErp('/send-sms/', compact('number', 'content'));

    }

    public function checkBasket($code)
    {
        return $this->sendToErp('/check-basket-barcode/', compact('code'));
    }

    public function sendBasketToErp($code)
    {
        return $this->sendToErp('/submit-basket-barcode/', compact('code'));
    }

}