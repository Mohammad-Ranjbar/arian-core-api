<?php

namespace App\Http\Controllers;

use App\Http\Resources\StopReasonResource;
use App\Repositories\StopReasonRepository;

class StopReasonController extends CrudController
{
    public function __construct(public readonly StopReasonRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(StopReasonResource::class);
    }
}
