<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlternativeCategoryResource;
use App\Repositories\AlternativeCategoryRepository;

class AlternativeCategoryController extends CrudController
{

    public function __construct(public readonly AlternativeCategoryRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AlternativeCategoryResource::class);
    }
}
