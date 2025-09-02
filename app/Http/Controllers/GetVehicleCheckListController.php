<?php

namespace App\Http\Controllers;

use App\Http\Resources\CheckListResource;
use App\Http\Resources\VehicleIncidentResource;
use App\Models\CheckList;
use App\Models\Vehicle;
use App\Models\VehicleIncident;
use Illuminate\Http\JsonResponse;

class GetVehicleCheckListController extends Controller
{

    public function __invoke(string $vin, string $type): JsonResponse
    {
        $vehicle = Vehicle::query()->where('vin', $vin)->firstOrFail();

        $checkList = CheckList::query()->where('type', $type)->whereHas('carModels', function ($q) use ($vehicle) {
            $q->where('car_models.id', $vehicle->getAttribute('car_model_id'));
        })->latest()->firstOrFail();

        $faults = VehicleIncident::query()->where('vin', $vin)->whereIn('element_id', $checkList->elements()->pluck('id')->toArray());

        return response()->json([
            'checkList' => CheckListResource::make($checkList),
            'faults' => VehicleIncidentResource::collection($faults)
        ]);

    }
}
