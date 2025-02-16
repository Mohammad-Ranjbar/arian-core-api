<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportExcelGatePermitRequest;
use App\Http\Resources\GatePermitResource;
use App\Jobs\ExportJob;
use App\Models\GatePermit;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\GatePermitRepository;
use App\Services\Erp\ErpCreateExitRequest;
use App\Services\Erp\ErpReturnRequest;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GatePermitController extends CrudController
{

    public function __construct(public readonly GatePermitRepository $repository,public VehicleController $vehicleController)
    {
        $this->applyAuthorizeResource();
        $this->setResource(GatePermitResource::class);
    }

    public function customStoreRules(): array
    {
        return [
            'item_type' => 'required|in:0,1,2',
            'vin' => ['required',
                'string',
                'exists:vehicles,vin',
                Rule::unique('gate_permits','model_id')->whereNull('deleted_at')
            ],
        ];
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request): mixed
    {
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(),$this->customStoreRules()));
        try {
            DB::beginTransaction();
            $validatedData['model_type'] = Vehicle::class;
            $validatedData['model_id'] = $validatedData['vin'];
            $validatedData['owner_id'] = Auth::id();
            $validatedData['owner_type'] = User::class;

            $location = Location::query()->find($validatedData['location_id']);

            if ($validatedData['type'] == 'exit') {

                $response = ErpCreateExitRequest::createExitRequest($validatedData['vin'], $validatedData['exit_type'],
                    $location->getAttribute('code'), $validatedData['item_type']);

                if ($response['status'] !== 'ok') {
                    return $this->responseError($response['message'], 400);
                }
            } elseif ($validatedData['type'] == 'enter') {
                $response = ErpReturnRequest::returnRequest($validatedData['vin'], $location->getAttribute('code'),$validatedData['item_type']);

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


    public function reportExcel(ReportExcelGatePermitRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['type'] = 'export-gate-permits';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = $request->validated();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'export job created',
            'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }


    /**
     * @throws AuthorizationException
     */
    public function print(GatePermit $gatePermit): JsonResponse
    {
        $this->authorize('gate-permits.print');
        try {
            $this->vehicleController->getData($gatePermit->getAttribute('model_id'));
        }catch (Exception $exception){
            logger($exception->getMessage());
        }
        return new JsonResponse(
            data: new GatePermitResource($gatePermit)
        );
    }
}
