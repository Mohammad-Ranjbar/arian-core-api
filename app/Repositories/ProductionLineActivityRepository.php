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
            'station_id'     => 'required|exists:stations,id',
            'stop_reason_id' => 'required|exists:stop_reasons,id',
            'stop_type_id'   => 'required|exists:stop_types,id',
            'description'    => 'nullable',
            'note'           => 'nullable',
            'start_at'       => 'nullable',
            'stop_at'         => 'nullable',
        ], 'update' => [
            'station_id'     => 'required|exists:stations,id',
            'stop_reason_id' => 'required|exists:stop_reasons,id',
            'stop_type_id'   => 'required|exists:stop_types,id',
            'description'    => 'nullable',
            'note'           => 'nullable',
            'start_at'       => 'nullable',
            'stop_at'         => 'nullable',
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