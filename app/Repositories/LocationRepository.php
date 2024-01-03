<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Location;

class LocationRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title'      => 'required|string|max:255',
            'code'       => 'required|string|max:255|unique:locations',
            'attributes' => 'required|array'

        ], 'update' => [
            'title'      => 'required|string|max:255',
            'code'       => 'required|string|max:255|unique:locations,code,',
            'attributes' => 'required|array'

        ]
    ];

    /**
     * @param  Location  $model
     */
    public function __construct(Location $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['code'] .= request()->route()->parameter('location');
        }
    }

}