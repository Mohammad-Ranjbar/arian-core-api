<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkGroupCenterResource;
use App\Repositories\WorkGroupCenterRepository;

class WorkGroupCenterController extends CrudController
{
    /**
     * @param  WorkGroupCenterRepository  $repository
     */
    public function __construct(WorkGroupCenterRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(WorkGroupCenterResource::class);
    }
}
