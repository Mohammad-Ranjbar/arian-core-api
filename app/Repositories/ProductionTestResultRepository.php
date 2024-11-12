<?php

namespace App\Repositories;

use App\Filters\AccessVehicleFilter;
use App\libs\BaseRepository;
use App\Models\ProductionTestResult;

class ProductionTestResultRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
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

    public function __construct(ProductionTestResult $model)
    {
        $this->model = $model;
    }


    public function getFilters(): array
    {
        $filters = parent::getFilters();
        $filters[] = AccessVehicleFilter::class;
        return $filters;
    }

    public function getReportData(array $data)
    {
        $query = $this->model::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['production_test_id'])) {
            $query = $query->where('production_test_id', $data['production_test_id']);
        }
        if (!empty($data['user_id'])) {
            $query = $query->where('user_id', $data['user_id']);
        }
        if (!empty($data['vin'])) {
            $query = $query->where('vin', $data['vin']);
        }

        return $query->get();
    }


}
