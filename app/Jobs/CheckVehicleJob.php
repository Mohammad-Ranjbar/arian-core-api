<?php

namespace App\Jobs;

use App\Http\Controllers\VehicleController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Validation\ValidationException;

class CheckVehicleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(public readonly string $vin)
    {
        //
    }


    public function handle(): void
    {
        $vehicleController = app()->make(VehicleController::class);
        $vehicleController->getData($this->vin);
    }
}
