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
            'reference'         => 'required|unique:car_models,reference',
            'title'             => 'required|string',
            'productionLines'   => 'required|array',
            'productionLines.*' => 'required|exists:production_lines,id'
        ],
        'update' => [
            'reference'         => 'required|unique:car_models,reference,',
            'title'             => 'required|string',
            'productionLines'   => 'required|array',
            'productionLines.*' => 'required|exists:production_lines,id'
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

    public function getOptions(): array
    {
        return $this->get('id', 'asc', request()->get('limit'))->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'driverName' => $item->driver->name,
                'driverMobile' => $item->driver->phone,
            ];
        })->all();
    }

}
