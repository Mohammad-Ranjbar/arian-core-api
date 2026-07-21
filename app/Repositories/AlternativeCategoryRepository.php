<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\AlternativeCategory;

class AlternativeCategoryRepository extends BaseRepository
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
     * @param  AlternativeCategory  $model
     */
    public function __construct(AlternativeCategory $model)
    {
        $this->model = $model;
    }

}