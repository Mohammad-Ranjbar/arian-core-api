<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

trait HttpServiceTrait
{
    public function getVehicleByVin($vin)
    {
        $res = Http::get(config('httpService.url').'/vehicle/'.$vin,[
            'token' => config('httpService.token')
        ]);
        return $res->json();
    }
}