<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductionLineActivity;

class ProductionLineActivityRepository extends BaseRepository
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
     * @param  ProductionLineActivity  $model
     */
    public function __construct(ProductionLineActivity $model)
    {
        $this->model = $model;
    }

}