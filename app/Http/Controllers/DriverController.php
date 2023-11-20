<?php

namespace App\Http\Controllers;

use App\Http\Resources\DriverResource;
use App\Repositories\DriverRepository;

class DriverController extends CrudController
{

    public function __construct(public readonly DriverRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(DriverResource::class);
    }
}
