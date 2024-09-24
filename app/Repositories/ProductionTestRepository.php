<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductionTest;

class ProductionTestRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:255',
            'carModels' => 'required|array',
            'carModels.*' => 'required|integer|exists:car_models,id',

        ], 'update' => [
            'title' => 'required|string|max:255',
            'carModels' => 'required|array',
            'carModels.*' => 'required|integer|exists:car_models,id',
        ]
    ];

    /**
     * @param  ProductionTest  $model
     */
    public function __construct(ProductionTest $model)
    {
        $this->model = $model;
    }

}
