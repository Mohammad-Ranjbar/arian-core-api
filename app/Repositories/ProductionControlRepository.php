<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductionControl;

class ProductionControlRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string',
            'file_id' => 'required|exists:files,uuid',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ], 'update' => [
            'title' => 'required|string',
            'file_id' => 'required|exists:files,uuid',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ]
    ];


    public function __construct(ProductionControl $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('production_control');
        }
    }

}
