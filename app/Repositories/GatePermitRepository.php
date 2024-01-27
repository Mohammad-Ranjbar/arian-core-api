<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\GatePermit;

class GatePermitRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'car_id' => 'required|exists:cars,id',
            'type'   => 'required',
            'status' => 'required',
        ], 'update' => [
            'car_id' => 'required|exists:cars,id',
            'type'   => 'required',
            'status' => 'required',
        ]
    ];

    /**
     * @param  GatePermit  $model
     */
    public function __construct(GatePermit $model)
    {
        $this->model = $model;
    }

}