<?php

namespace App\Http\Controllers;

use App\Http\Resources\CarModelResource;
use App\Repositories\CarModelRepository;

class CarModelController extends CrudController
{
    public function __construct(public readonly CarModelRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(CarModelResource::class);
    }
}
