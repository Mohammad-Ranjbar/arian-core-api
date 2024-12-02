<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\BodyOption;

class BodyOptionRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ], 'update' => [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ]
    ];

    /**
     * @param BodyOption $model
     */
    public function __construct(BodyOption $model)
    {
        $this->model = $model;
    }


    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id' => $item->code,
                'title' => $item->title .'-' . $item->code,

            ];
        })->all();
    }
}
