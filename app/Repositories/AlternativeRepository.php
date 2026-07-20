<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Alternative;

class AlternativeRepository extends BaseRepository
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
     * @param  Alternative  $model
     */
    public function __construct(Alternative $model)
    {
        $this->model = $model;
    }

}