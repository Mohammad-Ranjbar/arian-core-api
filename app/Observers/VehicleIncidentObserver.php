<?php

namespace App\Observers;

use App\Jobs\FaultIndexJob;
use App\Models\VehicleIncident;

class VehicleIncidentObserver
{


    /**
     * @param VehicleIncident $vehicleIncident
     * @return void
     */
    public function created(VehicleIncident $vehicleIncident): void
    {
        FaultIndexJob::dispatch($vehicleIncident->getAttribute('id'));
    }


    /**
     * @param VehicleIncident $vehicleIncident
     * @return void
     */
    public function updated(VehicleIncident $vehicleIncident): void
    {
        FaultIndexJob::dispatch($vehicleIncident->getAttribute('id'));
    }

    /**
     * @param VehicleIncident $vehicleIncident
     * @return void
     */
    public function deleted(VehicleIncident $vehicleIncident): void
    {
        FaultIndexJob::dispatch($vehicleIncident->getAttribute('id'));
    }

}
