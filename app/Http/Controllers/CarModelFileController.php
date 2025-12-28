<?php

namespace App\Http\Controllers;

use App\Http\Resources\CarModelFileResource;
use App\Repositories\CarModelFileRepository;

class CarModelFileController extends CrudController
{

    public function __construct(public readonly CarModelFileRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(CarModelFileResource::class);
    }
}
