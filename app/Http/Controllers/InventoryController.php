<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryResource;
use App\Repositories\InventoryRepository;

class InventoryController extends CrudController
{
    public function __construct(public readonly InventoryRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(InventoryResource::class);
    }
}
