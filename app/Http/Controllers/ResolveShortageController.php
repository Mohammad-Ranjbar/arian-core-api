<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveShortageRequest;
use App\Http\Resources\RegisterShortageResource;
use App\Models\RegisterShortage;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetVehicleLastLocation;
use Exception;
use Illuminate\Support\Facades\Auth;
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
            $vehicle = Vehicle::query()->where('vin', $validated['vin'])->first();

            $shortage = RegisterShortage::query()->find($validated['register_shortage_id']);
            $shortage->update([
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
            ]);
            $shortage->refresh();

            return RegisterShortageResource::make($shortage);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }
}
