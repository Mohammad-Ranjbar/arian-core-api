<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\CarModelLocation;

class CarModelLocationRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:200',
            'car_model_id' => 'required|exists:car_models,id',
            'code' => 'required|string|max:200'
        ], 'update' => [
            'title' => 'required|string|max:200',
            'car_model_id' => 'required|exists:car_models,id',
            'code' => 'required|string|max:200'
        ]
    ];

    /**
     * @param  CarModelLocation  $model
     */
    public function __construct(CarModelLocation $model)
    {
        $this->model = $model;
    }

}
