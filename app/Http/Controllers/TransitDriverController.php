<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitDriverResource;
use App\Repositories\TransitDriverRepository;

class TransitDriverController extends CrudController
{
    public function __construct(public readonly TransitDriverRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TransitDriverResource::class);
    }
}
