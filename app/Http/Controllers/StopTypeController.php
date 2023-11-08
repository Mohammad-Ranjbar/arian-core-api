<?php

namespace App\Http\Controllers;

use App\Http\Resources\StopTypeResource;
use App\Repositories\StopTypeRepository;

class StopTypeController extends CrudController
{
    /**
     * @param  StopTypeRepository  $repository
     */
    public function __construct(StopTypeRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(StopTypeResource::class);
    }
}
