<?php

namespace App\Http\Controllers;

use App\Http\Resources\ElementResource;
use App\Repositories\ElementRepository;

class ElementController extends CrudController
{
    public function __construct(public readonly ElementRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ElementResource::class);
    }
}
