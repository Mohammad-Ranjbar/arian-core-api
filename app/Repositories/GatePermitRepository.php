<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\GatePermit;

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

    public function getReportData(array $data)
    {
        $query = $this->model::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['types'])) {
            $query = $query->whereIn('type',  $data['types']);
        }
        if (!empty($data['location_id'])) {
            $query = $query->where('location_id',  $data['location_id']);
        }
        if (!empty($data['exit_type'])) {
            $query = $query->where('exit_type',  $data['exit_type']);
        }

        return $query->get();
    }

}
