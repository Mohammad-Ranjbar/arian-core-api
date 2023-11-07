<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


trait HttpServiceTrait
{
    public function getVehicleByVin($vin)
    {
        $res = Http::get(config('httpService.url').'/vehicle/'.$vin, [
            'token' => config('httpService.token')
        ]);
        return $res->json();
    }

    public function sendSms($number, $content)
    {
        try {
            $res = Http::get(config('httpService.url').'/send-sms/', [
                'token' => config('httpService.token'), 'number' => $number, 'content' => $content
            ]);
            return $res->json();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return false;
        }

    }

}