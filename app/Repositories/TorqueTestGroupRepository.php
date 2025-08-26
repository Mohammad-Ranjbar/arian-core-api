<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TorqueTestGroup;

class TorqueTestGroupRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:255',
            'register_date' => 'required|date_format:Y-m-d',
            'carModels' => 'required|array',
            'carModels.*' => 'integer|exists:car_models,id',
            'torqueMeasuringEquipments' => 'required|array',
            'torqueMeasuringEquipments.*' => 'required|exists:torque_measuring_equipment,id',
        ], 'update' => [
            'title' => 'required|string|max:255',
            'register_date' => 'required|date_format:Y-m-d',
            'carModels' => 'required|array',
            'carModels.*' => 'integer|exists:car_models,id',
            'torqueMeasuringEquipments' => 'required|array',
            'torqueMeasuringEquipments.*' => 'required|exists:torque_measuring_equipment,id',
        ]
    ];

    /**
     * @param  TorqueTestGroup  $model
     */
    public function __construct(TorqueTestGroup $model)
    {
        $this->model = $model;
    }

}
