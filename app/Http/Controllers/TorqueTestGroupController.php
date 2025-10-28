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
            'car_model_id' => [
                'required',
                'exists:car_models,id',
                Rule::unique('torque_test_groups')->where(function ($query) {
                    return $query->where('date', request()->input('date'))
                        ->where('car_model_id', request()->input('car_model_id'));
                })
            ],
            'torqueMeters' => 'required|array',
            'torqueMeters.*' => 'required|exists:torque_meters,id',
        ];
    }

    public function customUpdateRules(): array
    {
        return [
            'date' => 'required|date_format:Y-m-d',
            'car_model_id' => [
                'required',
                'exists:car_models,id',
                Rule::unique('torque_test_groups')->where(function ($query) {
                    return $query->where('date', request()->input('date'))
                        ->where('car_model_id', request()->input('car_model_id'));
                })->ignore(request()->input('{torque_test_group'))
            ],
        ];
    }

    public function screws(TorqueTestGroup $torqueTestGroup): AnonymousResourceCollection
    {
        $carModelsId = $torqueTestGroup->carModels()->pluck('id');
        $screws = Screw::query()->whereHas('carModels', function ($q) use ($carModelsId) {
            $q->whereIn('car_models.id', $carModelsId);
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
