<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\CheckList;

class CheckListRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string|max:200',
            'type' => 'required|string|max:200',
            'elements' => 'required|array',
            'elements.*' => 'required|exists:elements,id',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ], 'update' => [
            'title' => 'required|string|max:200',
            'type' => 'required|string|max:200',
            'elements' => 'required|array',
            'elements.*' => 'required|exists:elements,id',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ]
    ];

    /**
     * @param CheckList $model
     */
    public function __construct(CheckList $model)
    {
        $this->model = $model;
    }

}
