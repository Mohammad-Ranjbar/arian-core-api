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
use Illuminate\Validation\Rule;

class TorqueTestGroupController extends CrudController
{

    public bool $appendUserId = true;

    public function __construct(public readonly TorqueTestGroupRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TorqueTestGroupResource::class);
    }

    public function customStoreRules():array
    {
        return [
            'date' => 'required|date_format:Y-m-d',
            'torqueMeters' => 'required|exists:torque_meters,id',
            'car_model_id' => [
                'required',
                'exists:car_models,id',
                Rule::unique('torque_test_groups')->where(function ($query) {
                    return $query->where('date', request()->input('date'))
                        ->where('car_model_id', request()->input('car_model_id'));
                })
            ],
        ];
    }

    public function customUpdateRules(): array
    {
        return [
            'date' => 'required|date_format:Y-m-d',
            'torqueMeters' => 'required|exists:torque_meters,id',
            'car_model_id' => [
                'required',
                'exists:car_models,id',
                Rule::unique('torque_test_groups')->where(function ($query) {
                    return $query->where('date', request()->input('date'))
                        ->where('car_model_id', request()->input('car_model_id'));
                })->ignore(request()->input('torque_test_group'))
            ],
        ];
    }

    public function screws(TorqueTestGroup $torqueTestGroup): AnonymousResourceCollection
    {
        $carModelId = $torqueTestGroup->getAttribute('car_model_id');
        $screws = Screw::query()->whereHas('carModels', function ($screw) use ($carModelId) {
            $screw->where('car_models.id', $carModelId);
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
