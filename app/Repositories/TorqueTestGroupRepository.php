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