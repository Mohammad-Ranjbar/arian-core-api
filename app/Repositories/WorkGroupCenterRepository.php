<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\WorkGroupCenter;

class WorkGroupCenterRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title'              => 'required|string|unique:work_group_centers,title',
            'production_line_id' => 'required|exists:production_lines,id',
            'on_enter' => 'nullable|boolean',
            'on_exit' => 'nullable|boolean',
        ],
        'update' => [
            'title'              => 'required|string|unique:work_group_centers,title,',
            'production_line_id' => 'required|exists:production_lines,id',
            'on_enter' => 'nullable|boolean',
            'on_exit' => 'nullable|boolean',
        ]
    ];

    /**
     * @param  WorkGroupCenter  $model
     */
    public function __construct(WorkGroupCenter $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('work_group_center');
        }
    }

}
