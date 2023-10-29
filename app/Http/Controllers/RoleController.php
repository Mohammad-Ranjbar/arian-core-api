<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\libs\BaseRepository;
use App\Models\Role;
use App\Repositories\RoleRepository;

class RoleController extends CrudController
{
    public function __construct(RoleRepository $repository)
    {
        $this->setRepository($repository);
    }
}
