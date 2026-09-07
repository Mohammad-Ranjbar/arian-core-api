<?php

namespace App\Http\Controllers;

use App\Http\Resources\CarModelLocationResource;
use App\Repositories\CarModelLocationRepository;

class CarModelLocationController extends CrudController
{

    public function __construct(public readonly CarModelLocationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(CarModelLocationResource::class);
    }
}
