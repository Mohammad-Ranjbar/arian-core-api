<?php

namespace App\Console\Commands;

use App\Http\Controllers\VehicleController;
use App\Models\Vehicle;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAllVehicleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:fetch-all-vehicle-command';

    /**
     * @param VehicleController $controller
     */
    public function __construct(private readonly VehicleController $controller)
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $response = Http::get('http://172.16.0.52/api/vehicles')->collect();

        $bar = $this->output->createProgressBar(count($response));
        $bar->start();

        foreach ($response as $vin) {
            try {
                if(Vehicle::query()->where('vin', $vin->vin)->exists()) {
                    $bar->advance();
                    continue;
                }
                $this->controller->getData($vin);

                $bar->advance();
            } catch (Exception $exception) {
                logger($exception->getMessage());
            }

        }
        $bar->finish();
    }
}
