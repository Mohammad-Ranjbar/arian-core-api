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
//            'N5RL42DA5RP017833',
//            'N5RL42DA5RP017858',
//            'N5RL42DA5RP016436',
//            'N5RL42DA5RP017861',
//            'N5RL42DA5RP017837',
//            'N5RL42DA5RP017848',
//            'N5RL42DA5RP017623',
//            'N5RL42DA5RP017693',
//            'N5RL42DA5RP017855',
//            'N5RL42DA5RP017702',
//            'N5RL42DA5RP009821',
//            'N5RL42DA5RP017683',
//            'N5RL42DA5RP016501',
//
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
