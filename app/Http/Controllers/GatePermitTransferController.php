<?php

namespace App\Http\Controllers;

use App\Models\GatePermit;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\Erp\RestApi\ApproveExitZonesService;
use App\Services\Erp\RestApi\ApproveReturnZonesService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GatePermitTransferController extends Controller
{

    /**
     * @throws Throwable
     */
    public function __invoke(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('gate-permit-exit');

        try {
            // Hold the permit lock during ERP approval; never retry this external operation automatically.
            return DB::transaction(function () use ($vehicle) {
                $permit = GatePermit::query()->where('model_type', Vehicle::class)
                    ->where('model_id', $vehicle->getAttribute('vin'))->latest()->lockForUpdate()->firstOrFail();

                $result = match ($permit->getAttribute('type')) {
                    'exit' => resolve(ApproveExitZonesService::class)->send(
                        $permit->getAttribute('model_id'), (string) $permit->getAttribute('item_type'), model: $permit
                    ),
                    'enter' => resolve(ApproveReturnZonesService::class)->send(
                        $permit->getAttribute('model_id'), (string) $permit->getAttribute('item_type'), model: $permit
                    ),
                    default => null,
                };

                if ($result === null) {
                    return $this->responseError('نوع تردد مجوز معتبر نیست.', 422);
                }
                if (! $result['success']) {
                    // Return normally so the failed REST ERP job and its audit trail are committed.
                    return response()->json(['error' => $result['error']], $result['http_status']);
                }

                try {
                    // A savepoint keeps the ERP audit if either local write fails.
                    DB::transaction(function () use ($vehicle, $permit) {
                        $record = $vehicle->tracking()->create([
                            'model_type' => Location::class,
                            'model_id' => $permit->getAttribute('source_id'),
                            'type' => $permit->getAttribute('type'),
                            'user_id' => Auth::id(),
                        ]);

                        // Legacy SOAP dispatch is replaced by the synchronous REST call above.
                        // if ($record->getAttribute('type') == 'exit') {
                        //     $parameters = [
                        //         'url' => config('erp.gate'),
                        //         'parameters' => ['barcodeInfoP' => $permit->getAttribute('model_id'),'itemTypeP' => $permit->getAttribute('item_type')],
                        // //                    'method' => 'ApproveExit',
                        //         'method' => 'ApproveExit_Zone',
                        //         'section' => 'gate-permit-exit'
                        //     ];
                        // } elseif ($record->getAttribute('type') == 'enter') {
                        //     $parameters = [
                        //         'url' => config('erp.approve'),
                        //         'parameters' =>  ['vINP' => $permit->getAttribute('model_id'),'itemTypeP' => $permit->getAttribute('item_type')],
                        // //                    'method' => 'ApproveReturn',
                        //         'method' => 'ApproveReturn_Zone',
                        //         'section' => 'gate-permit-enter'
                        //     ];
                        // }
                        //
                        // $erpJob = ErpJob::query()->create(
                        //   array_merge($parameters,  [
                        //         'queue' => ErpJob::QUEUE_UNIQUE,
                        //         'user_id' => Auth::id(),
                        //         'model_type' => GatePermit::class,
                        //         'model_id' => $record->getAttribute('id'),
                        //     ])
                        // );
                        // ErpJobEvent::dispatch($erpJob);

                        if (! $record->exists || ! $permit->delete()) {
                            throw new RuntimeException('ثبت تردد ناموفق بود.');
                        }
                    });
                } catch (Throwable $exception) {
                    return $this->responseError($exception->getMessage(), 400);
                }

                return new JsonResponse(
                    data: ['message' => 'تردد ثبت گردید'],
                    status: Response::HTTP_OK
                );
            });
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }
}
