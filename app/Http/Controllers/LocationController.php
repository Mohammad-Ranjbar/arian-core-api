<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationResource;
use App\Repositories\LocationRepository;

class LocationController extends CrudController
{

    public function __construct(public readonly LocationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(LocationResource::class);
    }

}
