<?php

namespace App\Repositories;

use App\Http\Resources\RoleResource;
use App\libs\BaseRepository;
use App\Models\Role;

class RoleRepository extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(Role::class);
        $this->setResource(RoleResource::class);

        $this->setStoreValidation(['name' => 'required']);
        $this->setUpdateValidation(['name' => 'required']);
    }

}