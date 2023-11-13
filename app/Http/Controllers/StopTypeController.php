<?php

namespace App\Http\Controllers;

use App\Http\Resources\StopTypeResource;
use App\Repositories\StopTypeRepository;

class StopTypeController extends CrudController
{
    public function __construct(public readonly StopTypeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(StopTypeResource::class);
    }
}
