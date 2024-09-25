<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionTestResultResource;
use App\Repositories\ProductionTestResultRepository;
use Illuminate\Http\JsonResponse;

class ProductionTestResultController extends CrudController
{

    protected bool $appendUserId = true;

    public function __construct(public readonly ProductionTestResultRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionTestResultResource::class);
    }

}
