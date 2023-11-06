<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TransitSource;

class TransitSourceRepository extends BaseRepository
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
     * @param  TransitSource  $model
     */
    public function __construct(TransitSource $model)
    {
        $this->model = $model;
    }

}