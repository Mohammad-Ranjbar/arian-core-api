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
        'create' => [
            'type'                   => 'required|in:containers',
            'items'                  => 'array|required',
            'transit_source_id'      => 'required|exists:transit_sources,id',
            'transit_destination_id' => 'required|exists:transit_destinations,id',
            'code'                   => 'required|string|unique:bill_of_ladings,code',
            'request_no'             => 'required|string',
            'cottage'                => 'required|string',
            'weight'                 => 'required|string',
            'batch'                  => 'required|string',
            'description'            => 'nullable',
            'car_id'                 => 'required|exists:cars,id,deleted_at,NULL',
        ],
        'update' => []
    ];

    /**
     * @param  BillOfLading  $model
     */
    public function __construct(BillOfLading $model)
    {
        $this->model = $model;
    }

}