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

        ], 'update' => [
            'date' => 'required|date_format:Y-m-d',
            'car_model_id' => 'required|exists:car_models,id',
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
