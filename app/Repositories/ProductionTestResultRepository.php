<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductionTestResult;

class ProductionTestResultRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'vin' => 'required|exists:vehicles,vin',
            'files' => 'required|json',
            'description' => 'required',
            'production_test_id' => 'required|exists:production_tests,id',
        ], 'update' => [
            'vin' => 'required|exists:vehicles,vin',
            'files' => 'required|json',
            'description' => 'required',
            'production_test_id' => 'required|exists:production_tests,id',
        ]
    ];

    /**
     * @param  ProductionTestResult  $model
     */
    public function __construct(ProductionTestResult $model)
    {
        $this->model = $model;
    }

}
