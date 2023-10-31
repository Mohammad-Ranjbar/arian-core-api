<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\CarModel;
use App\Models\MakeCode;

class CarModelRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'reference' => 'required|unique:car_models,reference',
            'title'     => 'required',
        ],
        'update' => [
            'reference' => 'required|unique:car_models,reference,',
            'title'     => 'required',
        ]
    ];

    /**
     * @param  CarModel  $model
     */
    public function __construct(CarModel $model)
    {
        $this->model = $model;

        $this->rules['update']['reference'] .= request()->route()->parameter('carModel');
    }

}