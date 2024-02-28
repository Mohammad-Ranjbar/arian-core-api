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
            'type'      => 'required|in:enter,exit',
            'vin'       => 'required|string|exists:vehicles,vin',
            'exit_type' => ['nullable', 'required_if:type,exit']
        ], 'update' => [
            'type'      => 'required|in:enter,exit',
            'vin'       => 'required|string|exists:vehicles,vin',
            'exit_type' => ['nullable', 'required_if:type,exit']

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