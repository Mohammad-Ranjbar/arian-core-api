<?php

namespace App\Http\Controllers;

use App\Http\Resources\StopFactResource;
use App\Repositories\StopFactRepository;

class StopFactController extends CrudController
{
    public function __construct(public readonly StopFactRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(StopFactResource::class);
    }
}
