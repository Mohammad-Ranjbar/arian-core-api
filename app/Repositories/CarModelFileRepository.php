<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\CarModelFile;

class CarModelFileRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:255',
            'file_id' => 'required|string|exists:files,uuid',
            'car_model_id' => 'required|exists:car_models',
        ], 'update' => [
            'title' => 'required|string|max:255',
            'file_id' => 'nullable|string|exists:files,uuid',
            'car_model_id' => 'required|exists:car_models',
        ]
    ];

    /**
     * @param  CarModelFile  $model
     */
    public function __construct(CarModelFile $model)
    {
        $this->model = $model;
    }

}
