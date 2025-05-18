<?php

namespace App\Http\Controllers;

use App\Http\Resources\CopPlanResource;
use App\Repositories\CopPlanRepository;
use Illuminate\Http\Request;

class CopPlanController extends CrudController
{
    public function __construct(public readonly CopPlanRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(CopPlanResource::class);
    }
}
