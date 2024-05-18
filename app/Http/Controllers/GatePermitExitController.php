<?php

namespace App\Http\Controllers;

use App\Models\GatePermit;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GatePermitExitController extends Controller
{
    /**
     * @param Vehicle $vehicle
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function __invoke(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('gate-permit-exit');
        try {
            $permit = GatePermit::query()->where('model_type', Vehicle::class)->where('model_id',
                $vehicle->getAttribute('vin'))->latest()->firstOrFail();

            if ($permit->getAttribute('type') == 'exit') {
                $response = $this->exit($vehicle->getAttribute('vin'));
            } elseif ($permit->getAttribute('type') == 'enter') {
                $response = $this->enter($vehicle->getAttribute('vin'));
            }


            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 400);
            }

            $vehicle->tracking()->create([
                'model_type' => Location::class,
                'model_id' => 1,
                'type' => $permit->getAttribute('type'),
                'user_id' => Auth::id()
            ]);

            $permit->delete();

            return new  JsonResponse(
                data: ['message' => 'تردد ثبت گردید'],
                status: Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }

    }

    private function enter(string $vin): array
    {
        return ErpWsdlClient::run(config('erp.approve'), 'ApproveReturn',
            ['vINP' => $vin]);

    }

    /**
     * @param string $vin
     * @return array
     */
    private function exit(string $vin): array
    {
        return ErpWsdlClient::run(config('erp.gate'), 'ApproveExit',
            ['barcodeInfoP' => $vin]);

    }
}
