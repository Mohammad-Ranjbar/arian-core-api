<?php

namespace App\Http\Controllers;

use App\Http\Resources\SynchronousTypeResource;
use App\Repositories\SynchronousTypeRepository;

class SynchronousTypeController extends CrudController
{
    public function __construct(public readonly SynchronousTypeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SynchronousTypeResource::class);
    }
}
