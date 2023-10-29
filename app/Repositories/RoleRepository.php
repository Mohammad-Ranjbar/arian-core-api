<?php

namespace App\Repositories;

use App\Http\Resources\RoleResource;
use App\libs\BaseRepository;
use App\Models\Role;

class RoleRepository extends BaseRepository
{
    protected array $rules = [
        'create' => [
            'name' => 'required'
        ],
        'update'=>[
            'name' => 'required'
        ]
    ];

    public function __construct(Role $model)
    {
        $this->model = $model;
        $this->setResource(RoleResource::class);
    }

}