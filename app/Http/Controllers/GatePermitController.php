<?php

namespace App\Http\Controllers;

use App\Http\Resources\GatePermitResource;
use App\Repositories\GatePermitRepository;

class GatePermitController extends CrudController
{
    /**
     * @param  GatePermitRepository  $repository
     */
    public function __construct(GatePermitRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(GatePermitResource::class);
    }
}
