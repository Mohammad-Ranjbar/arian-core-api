<?php

namespace App\Http\Controllers;

use App\Http\Resources\BillOfLadingResource;
use App\Repositories\BillOfLadingRepository;

class BillOfLadingController extends CrudController
{
    /**
     * @param  BillOfLadingRepository  $repository
     */
    public function __construct(BillOfLadingRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(BillOfLadingResource::class);
    }
}
