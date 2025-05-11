<?php

namespace App\Http\Controllers;

use App\Http\Resources\TorqueMeasuringEquipmentResource;
use App\Repositories\TorqueMeasuringEquipmentRepository;

class TorqueMeasuringEquipmentController extends CrudController
{
    public function __construct(public readonly TorqueMeasuringEquipmentRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TorqueMeasuringEquipmentResource::class);
    }
}
