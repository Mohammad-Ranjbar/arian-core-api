<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\BillOfLading;

class BillOfLadingRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'transit_source_id'      => 'required|exists:transit_sources,id',
            'transit_destination_id' => 'required|exists:transit_destinations,id',
            'code'                   => 'required|string|unique:bill_of_ladings,code',
            'request_no'             => 'required|string',
            'priority'               => 'required|string',
            'cottage'                => 'required|string',
            'weight'                 => 'required|string',
            'batch'                  => 'required|string',
            'description'            => 'nullable',
            'car_id'                 => 'required|exists:cars,id',
        ], 'update' => [
            'transit_source_id'      => 'required|exists:transit_sources,id',
            'transit_destination_id' => 'required|exists:transit_destinations,id',
            'code'                   => 'required|string|unique:bill_of_ladings,code,',
            'request_no'             => 'required|string',
            'priority'               => 'required|string',
            'cottage'                => 'required|string',
            'weight'                 => 'required|string',
            'batch'                  => 'required|string',
            'description'            => 'nullable',
            'car_id'                 => 'required|exists:cars,id',
        ]
    ];

    /**
     * @param  BillOfLading  $model
     */
    public function __construct(BillOfLading $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['code'] .= request()->route()->parameter('bill_of_lading');
        }
    }

}