<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncidentResource;
use App\Repositories\IncidentRepository;

class IncidentController extends CrudController
{
    /**
     * @param  IncidentRepository  $repository
     */
    public function __construct(IncidentRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(IncidentResource::class);
    }
}
