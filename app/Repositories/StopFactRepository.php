<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\StopFact;

class StopFactRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title'        => 'required|string|unique:stop_facts,title',
        ], 'update' => [
            'title'        => 'required|string|unique:stop_facts,title,',
        ]
    ];

    /**
     * @param  StopFact  $model
     */
    public function __construct(StopFact $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('stop_fact');
        }
    }

}