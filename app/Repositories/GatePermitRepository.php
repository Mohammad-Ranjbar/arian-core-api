<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\GatePermit;
use Illuminate\Validation\Rule;

class GatePermitRepository extends BaseRepository
{

    public array $searchTerms = [
        'model_id'
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'type' => 'required|in:enter,exit',

            'exit_type' => ['nullable', 'required_if:type,exit', 'in:1,2'],
            'location_id' => ['required', 'exists:locations,id'],
        ], 'update' => [
            'type' => 'required|in:enter,exit',
            'vin' => 'required|string|exists:vehicles,vin',
            'exit_type' => ['nullable', 'required_if:type,exit', 'in:1,2'],
            'location_id' => ['required', 'exists:locations,id'],
        ]
    ];

    /**
     * @param GatePermit $model
     */
    public function __construct(GatePermit $model)
    {
        $this->model = $model;
    }

    public function customStoreRules(): array
    {
        return [
            'vin' => ['required',
                'string',
                'exists:vehicles,vin',
                Rule::unique('gate_permits','model_id')->whereNull('deleted_at')
            ],
        ];
    }

}
