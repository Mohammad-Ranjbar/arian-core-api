<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryResource;
use App\Repositories\InventoryRepository;

class InventoryController extends CrudController
{
    /**
     * @param  InventoryRepository  $repository
     */
    public function __construct(InventoryRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(InventoryResource::class);
    }
}
