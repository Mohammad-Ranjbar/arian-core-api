<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\UserGroup;

class UserGroupRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|unique:user_groups,title',
            'setting' => 'nullable|json'
        ], 'update' => [
            'title' => 'required|unique:user_groups,title,',
            'setting' => 'nullable|json'
        ]
    ];

    /**
     * @param  UserGroup  $model
     */
    public function __construct(UserGroup $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('user_group');
        }

    }

}
