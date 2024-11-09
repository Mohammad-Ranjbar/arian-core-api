<?php

namespace App\Repositories;

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
        ], 'update' => [
            'title' => 'required|string|max:255',
            'carModels' => 'nullable|array|exists:car_models,id',
            'height' => 'required|integer|between:1,2',
            'priority' => 'required|integer',
            'type' => 'required|string',
        ]
    ];

    /**
     * @param GeneralProductItem $model
     */
    public function __construct(GeneralProductItem $model)
    {
        $this->model = $model;
    }

}
