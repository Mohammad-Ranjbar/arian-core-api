<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Vehicle;

class VehicleRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [

        ], 'update' => [

        ]
    ];

    /**
     * @param  Vehicle  $model
     */
    public function __construct(Vehicle $model)
    {
        $this->model = $model;
    }


}