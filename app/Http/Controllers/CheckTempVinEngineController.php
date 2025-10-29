<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckTempVinEngineRequest;
use App\Http\Resources\TemporaryProductionResource;
use App\Http\Resources\VehicleResource;
use App\Http\Resources\VinEngineResource;
use App\Models\TemporaryProduction;
use App\Models\Vehicle;
use App\Models\VinEngine;
use Illuminate\Http\JsonResponse;

class CheckTempVinEngineController extends Controller
{
    public function __invoke(CheckTempVinEngineRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $vehicle = Vehicle::query()
            ->where('production_order_number', $validatedData['production_order_number'])
            ->with('makeCode')
            ->first();
        $vinEngine = VinEngine::query()->where('engine_no', $validatedData['engine_no'])->first();

        $tempVin = TemporaryProduction::query()->where('production_order_number', $validatedData['production_order_number'])->first();

//        $makeCodePrefix = collect(config('serial_barcodes.' . $vehicle->getRelation('makeCode')->reference));
//        $data = $makeCodePrefix->where('type', 'custom_engine')->first();

//        $prefix = is_array($data['prefix']) ? $data['prefix'][$vehicle->getRelation('carModel')] : $data['prefix'];

        return response()->json([
            'vehicle' => !empty($vehicle) ? VehicleResource::make($vehicle) : null,
            'serial_barcodes' => $vehicle->get('serialBarcodes'),
            'vinEngine' => !empty($vinEngine) ? VinEngineResource::make($vinEngine) : null,
            'tempVin' => !empty($tempVin) ? TemporaryProductionResource::make($tempVin) : null,
        ]);
    }
}
