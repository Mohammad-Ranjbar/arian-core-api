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
            'N5RLAK4B4S2000018',
            'N5RLAK4B4S2000019',
            'N5RLAK4B4S2000020',
            'N5RLAK4B4S2000021',
            'N5RLAK4B4S2000022',
            'N5RLAK4B4S2000023',
            'N5RLAK4B4S2000024',
            'N5RLAK4B4S2000025',
            'N5RLAK4B4S2000026',
            'N5RLAK4B4S2000027',
            'N5RLAK4B4S2000028',
            'N5RLAK4B4S2000029',
            'N5RLAK4B4S2000030',
            'N5RLAK4B4S2000031'
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
