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
            'item_type'               => 'required_if:type,vehicles,bodies|in:0,1,2',
            'items'                   => 'array|required',
            'location_source_id'      => 'required|exists:locations,id',
            'location_destination_id' => 'required|exists:locations,id',
            'request_no'              => 'nullable|required_if:type,containers',
            'cottage'                 => 'nullable|required_if:type,containers',
            'weight'                  => 'nullable|required_if:type,containers',
            'batch'                   => 'nullable|required_if:type,containers',
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
