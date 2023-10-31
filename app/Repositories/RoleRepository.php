<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Role;

class RoleRepository extends BaseRepository
{

    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'name'          => 'required|unique:roles,name',
            'guard_name'    => 'required',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ],
        'update' => [
            'name'          => 'required|unique:roles,name,',
            'guard_name'    => 'nullable',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]
    ];

    /**
     * @param  Role  $model
     */
    public function __construct(Role $model)
    {
        $this->model = $model;

        $this->rules['update']['name'] .= request()->route()->parameter('role');
    }



}