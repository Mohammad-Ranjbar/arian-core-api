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
//            "N5RL42DA5RP016686",
//            "N5RL42DA5RP016811",
//            "N5RL42DA5RP016833",
//            "N5RL42DA5RP016742",
//            "N5RL42DA5RP016825",
//            "N5RL42DA5RP016829",
//            "N5RL42DA5RP016830",
//            "N5RL42DA5RP016800",
//            "N5RL42DA5RP016837",
//            "N5RL42DA5RP016835",
//            "N5RL42DA5RP016819",
//            "N5RL42DA5RP016792",
//            "N5RL42DA5RP016783",
//            "N5RL42DA5RP016834",
//            "N5RL42DA5RP016832",
//            "N5RL42DA5RP016809",
//            "N5RL42DA5RP016655",
//            "N5RL42DA5RP016745",
//            "N5RL42DA5RP016756",
//            "N5RL42DA5RP016828",
//            "N5RL42DA5RP016816",
//            "N5RL42DA5RP016671",
//            "N5RL42DA5RP016788",
//            "N5RL42DA5RP016776",
//            "N5RL42DA5RP016842",
//            "N5RL42DA5RP016815",
//            "N5RL42DA5RP018014",
//            "N5RL42DA5RP016869",
//            "N5RL42DA5RP016778",
//            "N5RL42DA5RP016818",
//            "N5RL42DA5RP016878",
//            "N5RL42DA5RP016854",
//            "N5RL42DA5RP016872",
//            "N5RL42DA5RP016855",
//            "N5RL42DA5RP016873",
//            "N5RL42DA5RP016873",
//            "N5RL42DA5RP016699",
//            "N5RL42DA5RP016866",
//            "N5RL42DA5RP016880",
//            "N5RL42DA5RP016868",
//            "N5RL42DA5RP016876",
//            "N5RL42DA5RP016857",
//            "N5RL42DA5RP016848",
//            "N5RL42DA5RP016860",
//            "N5RL42DA5RP016689",
//            "N5RL42DA5RP016893",
//            "N5RL42DA5RP016884",
//            "N5RL42DA5RP016863",
//            "N5RL42DA5RP016856",
//            "N5RL42DA5RP016839",
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
