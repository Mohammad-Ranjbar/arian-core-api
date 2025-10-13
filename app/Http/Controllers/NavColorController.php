<?php

namespace App\Http\Controllers;

use App\Http\Resources\NavColorResource;
use App\Repositories\NavColorRepository;

class NavColorController extends CrudController
{

    public function __construct(public readonly NavColorRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(NavColorResource::class);
    }


}
