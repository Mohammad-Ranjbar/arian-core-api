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
            'title' => 'required|string|max:200',
            'is_active' => 'required|boolean',
            'user_id' => 'required|integer|exists:users,id',
        ], 'update' => [
            'title' => 'required|string|max:200,',
            'is_active' => 'required|boolean',
            'user_id' => 'required|integer|exists:users,id',
        ]
    ];

    /**
     * @param  AlternativeCategory  $model
     */
    public function __construct(AlternativeCategory $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('alternative_category');
        }
    }

}
