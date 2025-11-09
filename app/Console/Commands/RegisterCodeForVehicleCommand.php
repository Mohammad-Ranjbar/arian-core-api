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
            'N5RP12DT5SP000006',
            'N5RP12DT5SP000007',
            'N5RP12DT5SP000008',
            'N5RP12DT5SP000009',
            'N5RP12DT5SP000010',
            'N5RP12DT5SP000011',
            'N5RP12DT5SP000012',
            'N5RP12DT5SP000013',
            'N5RP12DT5SP000014',
            'N5RP12DT5SP000015',
            'N5RP12DT5SP000016',
            'N5RP12DT5SP000017',
            'N5RP12DT5SP000018',
            'N5RP12DT5SP000019',
            'N5RP12DT5SP000020',
            'N5RP12DT5SP000021',
            'N5RP12DT5SP000022',
            'N5RP12DT5SP000023',
            'N5RP12DT5SP000024',
            'N5RP12DT5SP000025',
            'N5RP12DT5SP000026',
            'N5RP12DT5SP000027',
            'N5RP12DT5SP000028',
            'N5RP12DT5SP000029',
            'N5RP12DT5SP000030',
            'N5RP12DT5SP000031',
            'N5RP12DT5SP000032',
            'N5RP12DT5SP000033',
            'N5RP12DT5SP000034',
            'N5RP12DT5SP000035',
            'N5RP12DT5SP000036',
            'N5RP12DT5SP000037',
            'N5RP12DT5SP000038',
            'N5RP12DT5SP000039',
            'N5RP12DT5SP000040',
            'N5RP12DT5SP000041',
            'N5RP12DT5SP000042',
            'N5RP12DT5SP000043',
            'N5RP12DT5SP000044',
            'N5RP12DT5SP000045',
            'N5RP12DT5SP000046',
            'N5RP12DT5SP000047',
            'N5RP12DT5SP000048',
            'N5RP12DT5SP000049',
            'N5RP12DT5SP000050',
            'N5RP12DT5SP000051',
            'N5RP12DT5SP000052',
            'N5RP12DT5SP000053',
            'N5RP12DT5SP000054',
            'N5RP12DT5SP000055',
            'N5RP12DT5SP000056',
            'N5RP12DT5SP000057',
            'N5RP12DT5SP000058',
            'N5RP12DT5SP000059',
            'N5RP12DT5SP000060',
            'N5RP12DT5SP000061',
            'N5RP12DT5SP000062',
            'N5RP12DT5SP000063',
            'N5RP12DT5SP000064',
            'N5RP12DT5SP000065',
            'N5RP12DT5SP000066',
            'N5RP12DT5SP000067',
            'N5RP12DT5SP000068',
            'N5RP12DT5SP000069',
            'N5RP12DT5SP000070',
            'N5RP12DT5SP000071',
            'N5RP12DT5SP000072',
            'N5RP12DT5SP000073',
            'N5RP12DT5SP000074',
            'N5RP12DT5SP000075',
            'N5RP12DT5SP000076',
            'N5RP12DT5SP000077',
            'N5RP12DT5SP000078',
            'N5RP12DT5SP000079',
            'N5RP12DT5SP000080',
            'N5RP12DT5SP000081',
            'N5RP12DT5SP000082',
            'N5RP12DT5SP000083',
            'N5RP12DT5SP000084',
            'N5RP12DT5SP000085',
            'N5RP12DT5SP000086',
            'N5RP12DT5SP000087',
            'N5RP12DT5SP000088',
            'N5RP12DT5SP000089',
            'N5RP12DT5SP000090',
            'N5RP12DT5SP000091',
            'N5RP12DT5SP000092',
            'N5RP12DT5SP000093',
            'N5RP12DT5SP000094',
            'N5RP12DT5SP000095',
            'N5RP12DT5SP000096',
            'N5RP12DT5SP000097',
            'N5RP12DT5SP000098',
            'N5RP12DT5SP000099',
            'N5RP12DT5SP000100',
            'N5RP12DT5SP000101',
            'N5RP12DT5SP000102'
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
                    'user_id' => 6,
                ]);
            }
        }
    }
}
