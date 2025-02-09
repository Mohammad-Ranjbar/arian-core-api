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

        $vehicles = [
//            "N5RSU7SN5R2902155",
//"N5RSU7SN5R2902164",
"N5RSU7SN5R2602487"
        ];


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
