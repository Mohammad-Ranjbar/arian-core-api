<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;

class UserController extends CrudController
{
    /**
     * @param  UserRepository  $repository
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(UserResource::class);
    }
}
