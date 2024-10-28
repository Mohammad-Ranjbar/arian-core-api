<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\IncidentCategory;

class IncidentCategoryRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:255',
        ], 'update' => [
            'title' => 'required|string|max:255',
        ]
    ];

    /**
     * @param  IncidentCategory  $model
     */
    public function __construct(IncidentCategory $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('incident_category');
        }
    }

}
