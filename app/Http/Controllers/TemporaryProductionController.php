<?php

namespace App\Http\Controllers;

use App\Http\Resources\TemporaryProductionResource;
use App\Repositories\TemporaryProductionRepository;

class TemporaryProductionController extends CrudController
{
    protected bool $appendUserId = true;
    public function __construct(public readonly TemporaryProductionRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TemporaryProductionResource::class);
    }
}
