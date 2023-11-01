<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\CarModel;

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
        if (request()->route()) {
            $this->rules['update']['reference'] .= request()->route()->parameter('car_model');
        }
    }

}