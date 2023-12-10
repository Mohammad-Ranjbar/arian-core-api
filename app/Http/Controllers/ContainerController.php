<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContainerResource;
use App\Repositories\ContainerRepository;

/**
 * @property ContainerRepository $repository
 */
class ContainerController extends CrudController
{
    /**
     * @param  ContainerRepository  $repository
     */
    public function __construct(ContainerRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(ContainerResource::class);
    }
}
