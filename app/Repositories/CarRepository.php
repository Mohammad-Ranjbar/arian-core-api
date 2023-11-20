<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Car;

class CarRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'driver_id'          => 'required|exists:drivers,id',
            'plate'              => 'required|json',
            'car'                => 'required|string',
            'transit_company_id' => 'required|exists:transit_companies,id',
        ], 'update' => [
            'driver_id'          => 'required|exists:drivers,id',
            'plate'              => 'required|json',
            'car'                => 'required|string',
            'transit_company_id' => 'required|exists:transit_companies,id',
        ]
    ];

    /**
     * @param  Car  $model
     */
    public function __construct(Car $model)
    {
        $this->model = $model;
    }

}