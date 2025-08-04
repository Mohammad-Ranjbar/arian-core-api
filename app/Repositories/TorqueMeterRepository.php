<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TorqueMeter;

class TorqueMeterRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
        ], 'update' => [
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
        ]
    ];

    /**
     * @param TorqueMeter $model
     */
    public function __construct(TorqueMeter $model)
    {
        $this->model = $model;
    }

}
