<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Inspection;

class InspectionRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'data'    => 'required|json',

        ], 'update' => [
            'data'    => 'required|json',
        ]
    ];

    /**
     * @param  Inspection  $model
     */
    public function __construct(Inspection $model)
    {
        $this->model = $model;
    }

}