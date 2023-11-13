<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitSourceResource;
use App\Repositories\TransitSourceRepository;

class TransitSourceController extends CrudController
{
    public function __construct(public readonly TransitSourceRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TransitSourceResource::class);
    }
}
