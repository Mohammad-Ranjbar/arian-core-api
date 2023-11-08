<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\StopReason;

class StopReasonRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|unique:stop_reasons,title'
        ], 'update' => [
            'title' => 'required|string|unique:stop_reasons,title,'
        ]
    ];

    /**
     * @param  StopReason  $model
     */
    public function __construct(StopReason $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('stop_reason');
        }
    }

}