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
            'salon_id' => 'required|exists:salons,id',
            'torque_measuring_equipment_id' => 'required|exists:torque_measuring_equipment,id',

        ], 'update' => [
            'title' => 'required|string|max:255',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
            'salon_id' => 'required|exists:salons,id',
            'torque_measuring_equipment_id' => 'required|exists:torque_measuring_equipment,id',
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
