<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationAttributeResource;
use App\Repositories\LocationAttributeRepository;

class LocationAttributeController extends CrudController
{

    public function __construct(public readonly LocationAttributeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(LocationAttributeResource::class);
    }
}
