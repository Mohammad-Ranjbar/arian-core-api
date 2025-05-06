<?php

namespace App\Http\Controllers;

use App\Http\Resources\TorqueCoefficientResource;
use App\Repositories\TorqueCoefficientRepository;

class TorqueCoefficientController extends CrudController
{
    public function __construct(public readonly TorqueCoefficientRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TorqueCoefficientResource::class);
    }
}
