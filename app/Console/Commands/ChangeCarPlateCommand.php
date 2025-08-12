<?php

namespace App\Console\Commands;

use App\Models\Car;
use Illuminate\Console\Command;

class ChangeCarPlateCommand extends Command
{

    protected $signature = 'app:change-plate';

    public function handle(): void
    {
        $list = Car::query()->whereHas('billOfLadings', function ($query) {
            $query->whereHas('vehicles', function ($q) {
                $q->where('type', 'vehicles');
            });
        })->latest()->get();

        foreach ($list as $car) {
            $plate = $car->plate;
            $newPlate = [
                "char"    => $plate['char'],
                "number1" => $plate['number3'],
                "number2" => $plate['number2'],
                "number3" => $plate['number1'],
                'changed' => true
            ];
            $car->plate = $newPlate;
            $car->save();
            $this->output->write('.');
        }
    }
}
