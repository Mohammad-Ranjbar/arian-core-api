<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkGroupCenterResource;
use App\Repositories\WorkGroupCenterRepository;

class WorkGroupCenterController extends CrudController
{
    public function __construct(public readonly WorkGroupCenterRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(WorkGroupCenterResource::class);
    }
}
