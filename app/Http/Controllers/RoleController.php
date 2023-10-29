<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\libs\BaseRepository;
use App\Models\Role;

class RoleController extends CrudController
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);

        $repository->setModel(Role::class);
        $repository->setResource(RoleResource::class);

        $repository->setStoreValidation(['name' => 'required']);
        $repository->setUpdateValidation(['name' => 'required']);
    }

}
