<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductBomResource;
use App\Repositories\ProductBomRepository;

class ProductBomController extends CrudController
{
    public function __construct(public readonly ProductBomRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductBomResource::class);
    }
}
