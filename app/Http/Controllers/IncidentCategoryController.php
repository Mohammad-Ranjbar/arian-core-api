<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncidentCategoryResource;
use App\Repositories\IncidentCategoryRepository;

class IncidentCategoryController extends CrudController
{

    public function __construct(public readonly IncidentCategoryRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(IncidentCategoryResource::class);
    }
}
