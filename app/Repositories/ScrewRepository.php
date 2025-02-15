<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Screw;

class ScrewRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string|max:255',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',

        ], 'update' => [
            'title' => 'required|string|max:255',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ]
    ];

    /**
     * @param Screw $model
     */
    public function __construct(Screw $model)
    {
        $this->model = $model;
    }

}
