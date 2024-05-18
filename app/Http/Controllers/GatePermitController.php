<?php

namespace App\Http\Controllers;

use App\Http\Resources\GatePermitResource;
use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\GatePermitRepository;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GatePermitController extends CrudController
{
    /**
     * @param  GatePermitRepository  $repository
     */
    public function __construct(public readonly GatePermitRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(GatePermitResource::class);
    }


    /**
     * @param  Request  $request
     * @return mixed
     */
    public function store(Request $request): mixed
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
        try {
            DB::beginTransaction();
            $validatedData['model_type'] = Vehicle::class;
            $validatedData['model_id'] = $validatedData['vin'];
            $validatedData['owner_id'] = Auth::id();
            $validatedData['owner_type'] = User::class;

            $location = Location::query()->find($validatedData['location_id'])->first();


            if ($validatedData['type'] == 'exit') {

//                if ($validatedData['exit_type'] == 1) {
//                    $typeExitErp = 'CONSIGNMENT EXIT';
//                } elseif ($validatedData['exit_type'] == 2) {
//                    $typeExitErp = 'PERMANENT EXIT';
//                }

                $response = $this->createExitRequest($validatedData['vin'], $validatedData['exit_type'],
                    $location->getAttribute('code'));

                if ($response['status'] !== 'ok') {
                    return $this->responseError($response['message'], 400);
                }
            } elseif ($validatedData['type'] == 'enter') {
                $response = $this->returnRequest($validatedData['vin'], $location->getAttribute('code'));

                if ($response['status'] !== 'ok') {
                    return $this->responseError($response['message'], 400);
                }
            }


            unset($validatedData['vin']);

            $saveItem = $this->repository->store($validatedData);
            $data = new $this->resource($saveItem);
            DB::commit();
            return $data;
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    private function createExitRequest(string $vin, string $type, string $locationCode): array
    {
        dd($vin,$type,$locationCode);
        return ErpWsdlClient::run(config('erp.exit_request'), 'CreateExitRequest',
            ['vINP' => $vin, 'typeOfExitP' => $type, 'destinationP' => $locationCode]);

    }

    private function returnRequest(string $vin, string $locationCode): array
    {
        return ErpWsdlClient::run(config('erp.return_request'), 'ReturnRequest',
            ['vINP' => $vin, 'destinationP' => $locationCode]);

    }

    public function oldGate(Vehicle $vehicle): JsonResponse
    {

        $response = ErpWsdlClient::run(config('erp.old_gate'), 'CreateVehicleExitStatus',
            ['vINP' => $vehicle->getAttribute('vin')]);

        if ($response['status'] == 'ok') {
            $vehicle->tracking()->create([
                'model_type' => Location::class,
                'model_id'   => 1,
                'type'       => 'exit',
                'user_id'    => Auth::id()
            ]);

            return response()->json(['message' => 'تردد مورد نظر ثبت گردید']);
        }

        return response()->json(['message' => 'ثبت تردد امکان پذیر نیست']);

    }
}
