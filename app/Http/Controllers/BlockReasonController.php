<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockReasonResource;
use App\Repositories\BlockReasonRepository;

class BlockReasonController extends CrudController
{
    public function __construct(public readonly BlockReasonRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(BlockReasonResource::class);
    }
}
