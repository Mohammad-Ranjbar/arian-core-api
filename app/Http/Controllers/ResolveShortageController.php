<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveShortageRequest;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetVehicleLastLocation;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ResolveShortageController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ResolveShortageRequest $request)
    {
        $validated = $request->validated();
        try {
            $vehicle = Vehicle::query()->where('vin', $validated['vin'])->with('carModel')->first();

            $carModel = $vehicle->getRelation('carModel');
            $response = resolve(ErpGetVehicleLastLocation::class)->get(
                vin: $parameters['itemNoArray'],
                carModelReference: $carModel->getAttribute('reference'),
            );
            dd($response);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }
}
