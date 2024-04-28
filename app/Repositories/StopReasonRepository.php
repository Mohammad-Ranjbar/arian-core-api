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
            'title'        => 'required|string',
            'stop_fact_id' => 'required|exists:stop_facts,id',
        ], 'update' => [
            'title'        => 'required|string',
            'stop_fact_id' => 'required|exists:stop_facts,id',
        ]
    ];

    /**
     * @param  StopReason  $model
     */
    public function __construct(StopReason $model)
    {
        $this->model = $model;
    }
    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id'    => $item->id,
                'title' => $item->fact ? $item->fact->title.' - '.$item->title : $item->title,
            ];
        })->all();
    }

}
