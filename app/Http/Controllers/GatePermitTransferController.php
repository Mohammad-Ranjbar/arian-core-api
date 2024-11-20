<?php

namespace App\Http\Controllers;

use App\Events\ErpJobEvent;
use App\Models\ErpJob;
use App\Models\GatePermit;
use App\Models\Location;
use App\Models\Vehicle;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GatePermitTransferController extends Controller
{
    /**
     * @param  Vehicle  $vehicle
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function __invoke(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('gate-permit-exit');
        try {
            $permit = GatePermit::query()->where('model_type', Vehicle::class)->where('model_id',
                $vehicle->getAttribute('vin'))->latest()->firstOrFail();

            $record = $vehicle->tracking()->create([
                'model_type' => Location::class,
                'model_id'   => 1,
                'type'       => $permit->getAttribute('type'),
                'user_id'    => Auth::id()
            ]);


            if ($record->getAttribute('type') == 'exit') {
                $parameters = [
                    'url' => config('erp.gate'),
                    'parameters' => ['barcodeInfoP' => $permit->getAttribute('model_id')],
                    'method' => 'ApproveExit',
                    'section' => 'gate-permit-exit'
                ];
            } elseif ($record->getAttribute('type') == 'enter') {
                $parameters = [
                    'url' => config('erp.approve'),
                    'parameters' =>  ['vINP' => $permit->getAttribute('model_id')],
                    'method' => 'ApproveReturn',
                    'section' => 'gate-permit-enter'
                ];
            }

            $erpJob = ErpJob::query()->create(
              array_merge($parameters,  [
                    'queue' => ErpJob::QUEUE_UNIQUE,
                    'user_id' => Auth::id(),
                    'model_type' => GatePermit::class,
                    'model_id' => $record->getAttribute('id'),
                ])
            );
            ErpJobEvent::dispatch($erpJob);
            $permit->delete();

            return new  JsonResponse(
                data: ['message' => 'تردد ثبت گردید'],
                status: Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

}
