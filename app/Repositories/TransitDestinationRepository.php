<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TransitDestination;

class TransitDestinationRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string'
        ], 'update' => [
            'title' => 'required|string'
        ]
    ];

    /**
     * @param  TransitDestination  $model
     */
    public function __construct(TransitDestination $model)
    {
        $this->model = $model;
    }

}