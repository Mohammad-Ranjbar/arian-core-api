<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserGroupResource;
use App\Repositories\UserGroupRepository;

class UserGroupController extends CrudController
{

    public function __construct(public readonly UserGroupRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(UserGroupResource::class);
    }
}
