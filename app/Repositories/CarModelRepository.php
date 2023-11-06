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
            'reference'          => 'required|unique:car_models,reference',
            'title'              => 'required|string',
            'production_line_id' => 'required|exists:production_lines,id'
        ],
        'update' => [
            'reference'          => 'required|unique:car_models,reference,',
            'title'              => 'required|string',
            'production_line_id' => 'required|exists:production_lines,id'
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