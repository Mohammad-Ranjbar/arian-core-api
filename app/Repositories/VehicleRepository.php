<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Vehicle;

class VehicleRepository extends BaseRepository
{

    /**
     * @param  Vehicle  $model
     */
    public function __construct(Vehicle $model)
    {
        $this->model = $model;
    }


}