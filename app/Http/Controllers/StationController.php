<?php

namespace App\Http\Controllers;

use App\Http\Resources\StationResource;
use App\Repositories\StationRepository;

class StationController extends CrudController
{
    /**
     * @param  StationRepository  $repository
     */
    public function __construct(StationRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(StationResource::class);
    }
}
