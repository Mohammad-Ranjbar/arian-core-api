<?php

namespace App\Http\Controllers;

use App\Http\Resources\TorqueMeterResource;
use App\Repositories\TorqueMeterRepository;

class TorqueMeterController extends CrudController
{

    public function __construct(public readonly TorqueMeterRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TorqueMeterResource::class);
    }
}
