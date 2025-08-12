<?php

namespace App\Console\Commands;

use App\Models\BillOfLading;
use App\Models\Car;
use App\Models\SaleInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ChangeCarPlateCommand extends Command
{

    protected $signature = 'app:change-plate';

    public function handle(): void
    {
        $list = Car::query()->whereHas('billOfLadings', function ($query) {
            $query->whereHas('vehicles',function ($q){
                $q->where('type','vehicles');
            });
        })->latest()->get();

        foreach ($list as $car) {
            $plate = $car->plate;
dd($plate);
        }
    }
}
