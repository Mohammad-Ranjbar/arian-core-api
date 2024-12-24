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
//            "N5RLAK4B4R2000004",
//            "N5RLAK4B4R2000005",
//            "N5RLAK4B4S2000006",
//            "N5RLAK4B4S2000007",
//            "N5RLAK4B4S2000008",
//            "N5RLAK4B4S2000009",
//            "N5RLAK4B4S2000010",
//            "N5RLAK4B4S2000011",
//            "N5RLAK4B4S2000012",
//            "N5RLAK4B4S2000013",
//            "N5RLAK4B4S2000014",
//            "N5RLAK4B4S2000015",
//            "N5RLAK4B4S2000016",
//            "N5RLAK4B4S2000017",
//            "N5RSU7SN5R2902285",
//            "N5RSU7SN5R2902286",
//            "N5RSU7SN5R2902287",
//            "N5RPU3NN5R2602620",
//            "N5RPU3NN5R2602621",
//            "N5RPU3NN5R2602622",
//            "N5RPU3NN5R2602623",
//            "N5RPU3NN5R2602624",
//            "N5RPU3NN5R2602625",
//            "N5RPU3NN5R2602626",
//            "N5RPU3NN5R2602627",
//            "N5RPU3NN5R2602628",
//            "N5RPU3NN5R2602629",
//            "N5RPU3NN5R2602630",
//            "N5RPU3NN5R2602631",
//            "N5RPU3NN5R2602632",
//            "N5RPU3NN5R2602633",
//            "N5RPU3NN5R2602634",
//            "N5RPU3NN5R2602635",
//            "N5RPU3NN5R2602636",
//            "N5RPU3NN5R2602637",
//            "N5RPU3NN5R2602638",
//            "N5RPU3NN5R2602639",
//            "N5RPU3NN5R2602640",
//            "N5RPU3NN5R2602641",
//            "N5RPU3NN5R2602642",
//            "N5RPU3NN5R2602643",
//            "N5RPU3NN5R2602644",
//            "N5RPU3NN5R2602645",
//            "N5RPU3NN5R2602646",
//            "N5RPU3NN5R2602647",
//            "N5RPU3NN5R2602648",
//            "N5RPU3NN5R2602649",
//            "N5RPU3NN5R2602650",
//            "N5RPU3NN5R2602651",
//            "N5RPU3NN5R2602652",
//            "N5RPU3NN5R2602653",
//            "N5RPU3NN5R2602654",
//            "N5RPU3NN5R2602655",
//            "N5RPU3NN5R2602656",
//            "N5RPU3NN5R2602657",
//            "N5RPU3NN5R2602658",
//            "N5RPU3NN5R2602659",
//            "N5RPU3NN5R2602660",
//            "N5RPU3NN5R2602661",
//            "N5RPU3NN5R2602662",
//            "N5RPU3NN5R2602663",
//            "N5RPU3NN5R2602664",
//            "N5RPU3NN5R2602665",
//            "N5RPU3NN5R2602666",
//            "N5RPU3NN5R2602667",
//            "N5RPU3NN5R2602668",
//            "N5RPU3NN5R2602669",
//            "N5RPU3NN5R2602670",
//            "N5RPU3NN5R2602671",
//            "N5RPU3NN5R2602672",
//            "N5RPU3NN5R2602673",
//            "N5RPU3NN5R2602674",
//            "N5RPU3NN5R2602675",
//            "N5RPU3NN5R2602676",
//            "N5RPU3NN5R2602677",
//            "N5RPU3NN5R2602678",
//            "N5RPU3NN5R2602679",
//            "N5RPU3NN5R2602680",
//            "N5RPU3NN5R2602681",
//            "N5RPU3NN5R2602682",
//            "N5RPU3NN5R2602683",
//            "N5RPU3NN5R2602684",
//            "N5RPU3NN5R2602685",
//            "N5RPU3NN5R2602686",
//            "N5RPU3NN5R2602687",
//            "N5RPU3NN5R2602688",
//            "N5RPU3NN5R2602689",
//            "N5RPU3NN5R2602690",
//            "N5RPU3NN5R2602691",
//            "N5RPU3NN5R2602692",
//            "N5RPU3NN5R2602693",
//            "N5RPU3NN5R2602694",
//            "N5RSU7SN5R2902288",
//            "N5RPU3NN5R2602695",
//            "N5RPU3NN5R2602696",
//            "N5RPU3NN5R2602697",
//            "N5RPU3NN5R2602698",
//            "N5RPU3NN5R2602699",
//            "N5RPU3NN5R2602700",
//            "N5RPU3NN5R2602701",
//            "N5RPU3NN5R2602702",
//            "N5RPU3NN5R2602703"
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
