<?php

namespace App\Http\Controllers;

use App\Http\Resources\BarcodeResource;
use App\Repositories\BarcodeRepository;

class BarcodeController extends CrudController
{
    /**
     * @param  BarcodeRepository  $repository
     */
    public function __construct(BarcodeRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(BarcodeResource::class);
    }
}
