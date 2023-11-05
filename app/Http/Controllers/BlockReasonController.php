<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockReasonResource;
use App\Repositories\BlockReasonRepository;

class BlockReasonController extends CrudController
{
    /**
     * @param  BlockReasonRepository  $repository
     */
    public function __construct(BlockReasonRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(BlockReasonResource::class);
    }
}
