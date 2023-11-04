<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\VehicleIncident;

class VehicleIncidentRepository extends BaseRepository
{
    /**
     * @param  VehicleIncident  $model
     */
    public function __construct(VehicleIncident $model)
    {
        $this->model = $model;

    }

}