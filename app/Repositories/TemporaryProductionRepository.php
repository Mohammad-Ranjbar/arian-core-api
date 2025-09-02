<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TemporaryProduction;

class TemporaryProductionRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'production_order_number' => 'required|string|max:255|exists:vehicles,production_order_number',
            'temp_vin' => 'required|string|max:255',
        ], 'update' => [
            'production_order_number' => 'required|string|max:255|exists:vehicles,production_order_number',
            'temp_vin' => 'required|string|max:255',
        ]
    ];

    /**
     * @param TemporaryProduction $model
     */
    public function __construct(TemporaryProduction $model)
    {
        $this->model = $model;
    }

}
