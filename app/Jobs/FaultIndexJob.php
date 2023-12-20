<?php

namespace App\Jobs;

use App\Http\Resources\VehicleIncidentResource;
use App\Models\VehicleIncident;
use App\Services\IndexElasticSearch\FaultIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FaultIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private $id)
    {
        //
    }


    public function handle(): void
    {
        $vehicleIncident = VehicleIncident::query()->find($this->id);
        if (!isset($vehicleIncident)){
            FaultIndexService::remove($this->id);
        }

        FaultIndexService::index(new VehicleIncidentResource($vehicleIncident));
    }
}
