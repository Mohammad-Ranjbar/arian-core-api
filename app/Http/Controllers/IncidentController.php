<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncidentResource;
use App\Repositories\IncidentRepository;

class IncidentController extends CrudController
{
    public function __construct(public readonly IncidentRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(IncidentResource::class);
    }


}
