<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitSourceResource;
use App\Repositories\TransitSourceRepository;

class TransitSourceController extends CrudController
{
    /**
     * @param  TransitSourceRepository  $repository
     */
    public function __construct(TransitSourceRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(TransitSourceResource::class);
    }
}
