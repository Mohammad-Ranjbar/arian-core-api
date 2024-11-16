<?php

namespace App\Console\Commands;

use App\Models\CarModel;
use App\Models\Vehicle;
use Illuminate\Console\Command;

class SyncMakeCodeIdToCarModelCommand extends Command
{

    protected $signature = 'app:sync-make-code-id-to-car-model-command';

    protected $description = 'Command description';

    public function handle():void
    {
        $carModels = CarModel::query()->whereNull('make_code_id')->get();

        $carModels->each(function (CarModel $carModel) {
            $makeCodeId = Vehicle::query()->where('car_model_id',$carModel->getAttribute('id'))->first()->make_code_id;

            $carModel->update(['make_code_id' => $makeCodeId]);
        });
    }
}
