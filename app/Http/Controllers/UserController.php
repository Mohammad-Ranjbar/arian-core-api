<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;

class UserController extends CrudController
{
    public function __construct(public readonly UserRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(UserResource::class);
    }
}
