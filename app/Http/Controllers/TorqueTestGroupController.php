<?php

namespace App\Http\Controllers;

use App\Http\Resources\TorqueTestGroupResource;
use App\Repositories\TorqueTestGroupRepository;

class TorqueTestGroupController extends CrudController
{

    public function __construct(public readonly TorqueTestGroupRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TorqueTestGroupResource::class);
    }
}
