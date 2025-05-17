<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\CopPlan;
use App\Models\ItemInputChecklist;

class CopPlanRepository extends BaseRepository
{
    /**
     * @var array|string[]
     */
    public array $searchTerms = [
        'title'
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ],
        'update' => [
            'title' => 'required',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ]
    ];

    /**
     * @param CopPlan $model
     */
    public function __construct(CopPlan $model)
    {
        $this->model = $model;
    }

    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
            ];
        })->all();
    }

}
