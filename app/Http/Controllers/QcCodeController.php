<?php

namespace App\Http\Controllers;

use App\Http\Resources\QcCodeResource;
use App\Repositories\QcCodeRepository;

class QcCodeController extends CrudController
{

    public function __construct(public readonly QcCodeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(QcCodeResource::class);
    }
}
