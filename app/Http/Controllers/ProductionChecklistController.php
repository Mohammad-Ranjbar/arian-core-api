<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionChecklistResource;
use App\Repositories\ProductionChecklistRepository;
use Illuminate\Http\Request;

class ProductionChecklistController extends CrudController
{
    public function __construct(public readonly ProductionChecklistRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionChecklistResource::class);
    }
}
