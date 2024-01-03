<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\LocationAttribute;

class LocationAttributeRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'attribute'   => 'required|string|max:255',
            'value'       => 'required|array',
            'value.*'     => 'required|string',
            'location_id' => 'required|integer|exists:locations,id'
        ], 'update' => [
            'attribute' => 'required|string|max:255',
            'value'     => 'required|array',
            'value.*'   => 'required|string',
            'location_id' => 'required|integer|exists:locations,id'
        ]
    ];

    /**
     * @param  LocationAttribute  $model
     */
    public function __construct(LocationAttribute $model)
    {
        $this->model = $model;
    }

}