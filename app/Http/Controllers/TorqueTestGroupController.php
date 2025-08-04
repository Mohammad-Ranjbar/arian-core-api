<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScrewResource;
use App\Http\Resources\TorqueTestGroupResource;
use App\Http\Resources\VehicleTorqueTestResource;
use App\Models\Screw;
use App\Models\TorqueTestGroup;
use App\Models\VehicleTorqueTest;
use App\Repositories\TorqueTestGroupRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TorqueTestGroupController extends CrudController
{

    public function __construct(public readonly TorqueTestGroupRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TorqueTestGroupResource::class);
    }

    public function screws(TorqueTestGroup $torqueTestGroup): AnonymousResourceCollection
    {
        $carModelsId = $torqueTestGroup->carModels()->pluck('id');
        $screws = Screw::query()->whereHas('carModels', function ($q) use ($carModelsId) {
            $q->whereIn('carModels.id', $carModelsId);
        })->get();

        return ScrewResource::collection($screws);
    }

    public function tests(TorqueTestGroup $torqueTestGroup): AnonymousResourceCollection
    {
        $data = VehicleTorqueTest::query()
            ->where('torque_test_group_id', $torqueTestGroup->getAttribute('id'))
            ->get();

        return VehicleTorqueTestResource::collection($data);
    }
}
