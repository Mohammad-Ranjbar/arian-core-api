<?php

namespace App\Http\Controllers;

use App\Http\Resources\StopFactResource;
use App\Repositories\StopFactRepository;

class StopFactController extends CrudController
{
    /**
     * @param  StopFactRepository  $repository
     */
    public function __construct(StopFactRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(StopFactResource::class);
    }
}
