<?php

namespace App\Http\Controllers;

use App\Http\Resources\CarResource;
use App\Repositories\CarRepository;

class CarController extends CrudController
{
    public function __construct(public readonly CarRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(CarResource::class);
    }
}
