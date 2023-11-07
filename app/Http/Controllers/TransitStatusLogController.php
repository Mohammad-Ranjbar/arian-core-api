<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitStatusLogResource;
use App\Repositories\TransitStatusLogRepository;

class TransitStatusLogController extends CrudController
{
    /**
     * @param  TransitStatusLogRepository  $repository
     */
    public function __construct(TransitStatusLogRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(TransitStatusLogResource::class);
    }
}
