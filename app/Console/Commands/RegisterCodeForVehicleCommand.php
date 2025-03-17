<?php

namespace App\Console\Commands;

use App\Models\Barcode;
use App\Models\GeneralProductItem;
use App\Models\Vehicle;
use Illuminate\Console\Command;

class RegisterCodeForVehicleCommand extends Command
{

    protected $signature = 'register:code';


    protected $description = 'Command description';


    public function handle(): void
    {

//        $vehicles = [
//            "N5RLAK4B4S2000034",
//            "N5RLAK4B4S2000035",
//            "N5RLAK4B4S2000036",
//            "N5RLAK4B4S2000037",
//            "N5RLAK4B4S2000038",
//            "N5RLAK4B4S2000039",
//            "N5RLAK4B4S2000040",
//            "N5RLAK4B4S2000041",
//            "N5RLAK4B4S2000042",
//            "N5RLAK4B4S2000043",
//            "N5RLAK4B4S2000044",
//            "N5RLAK4B4S2000045",
//        ];


        foreach ($vehicles as $vehicle) {
            $carModelId = Vehicle::query()->where('vin', $vehicle)->first()->car_model_id;

            $generalProductItems = GeneralProductItem::query()->whereHas('carModels', function ($q) use ($carModelId) {
                return $q->where('car_model_id', $carModelId);
            })->get()->pluck('id')->toArray();

            foreach ($generalProductItems as $generalProductItem) {
                Barcode::query()->create([
                    'vin' => $vehicle,
                    'general_product_item_id' => $generalProductItem,
                    'code' => 'noLabel',
                    'user_id' => 1,
                ]);
            }
        }
    }
}
