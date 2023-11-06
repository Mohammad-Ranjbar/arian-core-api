<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TransitActivity;

class TransitActivityRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'transit_source_id'      => 'required|exists:transit_sources,id',
            'transit_destination_id' => 'required|exists:transit_destinations,id',
            'transit_driver_id'      => 'required|exists:transit_drivers,id',
            'bill_of_lading'         => 'required|string',
            'request_no'             => 'required|string',
            'container'              => 'required|string|unique:transit_activities',
            'cottage'                => 'required|string',
            'weight'                 => 'required|string',
            'batch'                  => 'required|string',
            'description'            => 'nullable',
        ], 'update' => [
            'transit_source_id'      => 'required|exists:transit_sources,id',
            'transit_destination_id' => 'required|exists:transit_destinations,id',
            'transit_driver_id'      => 'required|exists:transit_drivers,id',
            'bill_of_lading'         => 'required|string',
            'request_no'             => 'required|string',
            'container'              => 'required|string|unique:transit_activities,container,',
            'cottage'                => 'required|string',
            'weight'                 => 'required|string',
            'batch'                  => 'required|string',
            'description'            => 'nullable',
        ]
    ];

    /**
     * @param  TransitActivity  $model
     */
    public function __construct(TransitActivity $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['container'] .= request()->route()->parameter('transitActivity');
        }
    }

}