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
            'type'                    => 'required|in:containers,vehicles,bodies',
            'items'                   => 'array|required',
            'location_source_id'      => 'required|exists:locations,id',
            'location_destination_id' => 'required|exists:locations,id',
            'code'                    => 'required|string|unique:bill_of_ladings,code',
            'request_no'              => 'required_if:type,vehicles',
            'cottage'                 => 'required_if:type,vehicles',
            'weight'                  => 'required_if:type,vehicles',
            'batch'                   => 'required_if:type,vehicles',
            'description'             => 'nullable',
            'car_id'                  => 'required|exists:cars,id,deleted_at,NULL',
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
