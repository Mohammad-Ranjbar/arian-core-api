<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TransitStatusLog;

class TransitStatusLogRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'transit_activity_id' => 'required|exists:transit_activities,id',
            'user_id'             => 'required|exists:users,id',
            'status'              => 'required|string',
        ], 'update' => [
            'transit_activity_id' => 'required|exists:transit_activities,id',
            'user_id'             => 'required|exists:users,id',
            'status'              => 'required|string',
        ]
    ];

    /**
     * @param  TransitStatusLog  $model
     */
    public function __construct(TransitStatusLog $model)
    {
        $this->model = $model;
    }

}