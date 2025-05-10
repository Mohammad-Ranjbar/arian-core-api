<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TorqueCoefficient;
use App\Models\TorqueMeasuringEquipment;

class TorqueCoefficientRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'low_1'     => 'required|numeric',
            'low_2'     => 'required|numeric',
            'low_3'     => 'required|numeric',
            'high_1'    => 'required|numeric',
            'high_2'    => 'required|numeric',
            'high_3'    => 'required|numeric',
            'high_4'    => 'required|numeric',
            'is_active' => 'required|boolean',
        ],
        'update' => [
            'low_1'     => 'required|numeric',
            'low_2'     => 'required|numeric',
            'low_3'     => 'required|numeric',
            'high_1'    => 'required|numeric',
            'high_2'    => 'required|numeric',
            'high_3'    => 'required|numeric',
            'high_4'    => 'required|numeric',
            'is_active' => 'required|boolean',
        ]
    ];

    /**
     * @param TorqueCoefficient $model
     */
    public function __construct(TorqueCoefficient $model)
    {
        $this->model = $model;
    }
}
