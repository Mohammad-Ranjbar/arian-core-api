<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\StopType;

class StopTypeRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|unique:stop_types,title'
        ], 'update' => [
            'title' => 'required|string|unique:stop_types,title,'
        ]
    ];

    /**
     * @param  StopType  $model
     */
    public function __construct(StopType $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('stop_type');
        }
    }

}