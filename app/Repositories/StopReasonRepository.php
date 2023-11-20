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
            'title'        => 'required|string|unique:stop_reasons,title',
            'stop_fact_id' => 'required|exists:stop_facts,id',
        ], 'update' => [
            'title'        => 'required|string|unique:stop_reasons,title,',
            'stop_fact_id' => 'required|exists:stop_facts,id',
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
    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id'    => $item->id,
                'title' => $item->fact->title.' - '.$item->title,
            ];
        })->all();
    }

}