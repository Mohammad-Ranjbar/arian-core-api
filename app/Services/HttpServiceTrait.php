<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


trait HttpServiceTrait
{

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

    public function checkBasket($code)
    {
        return (object)[
            'status' => true
        ];
        return $this->sendToErp('/check-basket-barcode/', compact('code'));
    }

    public function sendBasketToErp($code)
    {
        return (object)[
            'status' => true
        ];
        return $this->sendToErp('/submit-basket-barcode/', compact('code'));
    }

}