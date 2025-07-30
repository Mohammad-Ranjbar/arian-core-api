<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\VinEngine;

class VinEngineRepository extends BaseRepository
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
     * @param VinEngine $model
     */
    public function __construct(VinEngine $model)
    {
        $this->model = $model;
    }

    public function custom()
    {

    }

}
