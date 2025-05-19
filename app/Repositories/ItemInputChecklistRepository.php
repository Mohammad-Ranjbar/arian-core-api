<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ItemInputChecklist;

class ItemInputChecklistRepository extends BaseRepository
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
            'file_id' => 'required|exists:files,uuid',
        ],
        'update' => [
            'title' => 'required',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
            'file_id' => 'required|exists:files,uuid',
        ]
    ];

    /**
     * @param ItemInputChecklist $model
     */
    public function __construct(ItemInputChecklist $model)
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
