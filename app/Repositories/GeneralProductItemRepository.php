<?php

namespace App\Repositories;

use App\Filters\GeneralProductItemByMakeCodeFilter;
use App\libs\BaseRepository;
use App\Models\GeneralProductItem;

class GeneralProductItemRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string|max:255',
            'carModels' => 'nullable|array|exists:car_models,id',
            'height' => 'required|integer|between:1,2',
            'priority' => 'required|integer',
            'type' => 'required|string',
            'stations' => 'nullable|array',
            'stations.*' => 'integer|exists:stations,id',
            'part_type' => 'nullable|string|in:kd,local',
            'label_status' => 'nullable|boolean',
            'hack_status' => 'nullable|boolean',
            'count_lable' => 'nullable|integer|in:1,2',

        ], 'update' => [
            'title' => 'required|string|max:255',
            'carModels' => 'nullable|array|exists:car_models,id',
            'height' => 'required|integer|between:1,2',
            'priority' => 'required|integer',
            'type' => 'required|string',
            'stations' => 'nullable|array',
            'stations.*' => 'integer|exists:stations,id',
            'part_type' => 'nullable|string|in:kd,local',
            'label_status' => 'nullable|boolean',
            'hack_status' => 'nullable|boolean',
            'count_lable' => 'nullable|integer|in:1,2',
        ]
    ];

    /**
     * @param GeneralProductItem $model
     */
    public function __construct(GeneralProductItem $model)
    {
        $this->model = $model;
        $this->filters[] =  GeneralProductItemByMakeCodeFilter::class;
    }

}
