<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Repositories\RoleRepository;

class RoleController extends CrudController
{
    public function __construct(public readonly RoleRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(RoleResource::class);
    }
}
