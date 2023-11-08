<?php

namespace App\Http\Controllers;

use App\Http\Resources\StopReasonResource;
use App\Repositories\StopReasonRepository;

class StopReasonController extends CrudController
{
    /**
     * @param  StopReasonRepository  $repository
     */
    public function __construct(StopReasonRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(StopReasonResource::class);
    }
}
