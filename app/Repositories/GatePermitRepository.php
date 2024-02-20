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
            'type' => 'required|in:enter,exit',
            'vin'  => 'required|string|exists:vehicles,vin',
        ], 'update' => [
            'type' => 'required|in:enter,exit',
            'vin'  => 'required|string|exists:vehicles,vin',
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