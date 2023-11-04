<?php

namespace App\Http\Controllers;

use App\Http\Resources\ElementResource;
use App\Repositories\ElementRepository;
use Illuminate\Http\Request;

class ElementController extends CrudController
{
    /**
     * @param  ElementRepository  $repository
     */
    public function __construct(ElementRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(ElementResource::class);
    }
}
