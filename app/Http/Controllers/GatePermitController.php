<?php

namespace App\Http\Controllers;

use App\Http\Resources\GatePermitResource;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\GatePermitRepository;
use App\Services\Erp\ErpWsdlClient;
use Exception;
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
            unset($validatedData['vin']);

            $response = $this->createExitRequest($validatedData['vin'],$validatedData['type']);

            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 400);
            }



            $saveItem = $this->repository->store($validatedData);
            $data = new $this->resource($saveItem);
            DB::commit();
            return $data;
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    private function createExitRequest(string $vin,string $type): array
    {
        return ErpWsdlClient::run(config('erp.exit_request'), 'CreateExitRequest',
            ['vINP' => $vin,'typeOfExitP' => $type]);

    }

    private function approveReturn(string $vin,string $type): array
    {
        return ErpWsdlClient::run(config('erp.approve'), 'ApproveReturn',
            ['vINP' => $vin]);

    }

    private function returnRequest(string $vin): array
    {
        return ErpWsdlClient::run(config('erp.return_request'), 'ReturnRequest',
            ['vINP' => $vin]);

    }
}
