<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ActionLog;
use Illuminate\Database\Eloquent\Builder;

class ActionLogRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [

        ], 'update' => [

        ]
    ];

    /**
     * @param ActionLog $model
     */
    public function __construct(ActionLog $model)
    {
        $this->model = $model;
    }



}
