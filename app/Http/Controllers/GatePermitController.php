<?php

namespace App\Http\Controllers;

use App\Filters\ModelIdFilter;
use App\Filters\VinFilter;
use App\Http\Requests\ReportExcelGatePermitRequest;
use App\Http\Resources\GatePermitResource;
use App\Jobs\ExportJob;
use App\Models\GatePermit;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\GatePermitRepository;
// use App\Services\Erp\ErpReturnRequest;
use App\Services\Erp\RestApi\ExitPrintsService;
use App\Services\Erp\RestApi\ExitRequestsService;
use App\Services\Erp\RestApi\ReturnRequestsService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GatePermitController extends CrudController
{

    public function __construct(public readonly GatePermitRepository $repository, public VehicleController $vehicleController)
    {
        $this->applyAuthorizeResource();
        $this->setResource(GatePermitResource::class);
    }

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $data = $this->repository->model->newQuery()->latest('created_at');
        Pipeline::send($data)->through([
            ModelIdFilter::class,
        ])->thenReturn();
            $data = $data->forAuthenticatedUser()->paginate($request->get('per_page', 15));
        return $this->collect($data);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request): mixed
    {
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(), $this->customStoreRules()));
        try {
            $returnResult = null;
            if ($validatedData['type'] === 'enter') {
                $location = Location::query()->findOrFail($validatedData['location_id']);
                $returnResult = resolve(ReturnRequestsService::class)->sendOrRetry(
                    $validatedData['vin'], (string) $location->code, (string) $validatedData['item_type']
                );
                if (! $returnResult['success']) {
                    throw new HttpResponseException(response()->json([
                        'error' => $returnResult['error'], 'rest_erp_job_id' => $returnResult['rest_erp_job_id'],
                    ], $returnResult['http_status']));
                }
            }

            return DB::transaction(function () use ($validatedData, $returnResult) {
                $validatedData['model_type'] = Vehicle::class;
                $validatedData['model_id'] = $validatedData['vin'];
                $validatedData['owner_id'] = Auth::id();
                $validatedData['owner_type'] = User::class;

                $location = Location::query()->findOrFail($validatedData['location_id']);

                // if ($validatedData['type'] == 'exit') {
                //     $response = ErpCreateExitRequest::createExitRequest($validatedData['vin'], $validatedData['exit_type'],
                //         $location->getAttribute('code'), $validatedData['item_type']);
                //     if ($response['status'] !== 'ok') {
                //         return $this->responseError($response['message'], 400);
                //     }
                // }
                // if ($validatedData['type'] == 'enter') {
                //     $response = ErpReturnRequest::returnRequest($validatedData['vin'], $location->getAttribute('code'), $validatedData['item_type']);
                //     if ($response['status'] !== 'ok') {
                //         throw new HttpResponseException($this->responseError($response['message'], 400));
                //     }
                // }

                unset($validatedData['vin']);
                $saveItem = $this->repository->store($validatedData);
                $data = new $this->resource($saveItem);

                if ($validatedData['type'] == 'exit') {
                    $result = resolve(ExitRequestsService::class)->dispatch(
                        $saveItem->getAttribute('model_id'), $saveItem->getAttribute('exit_type'),
                        (string) $location->getAttribute('code'), (string) $saveItem->getAttribute('item_type'),
                        model: $saveItem
                    );
                    if (! $result['success']) {
                        throw new HttpResponseException(response()->json(['error' => $result['error']], $result['http_status']));
                    }

                    $printResult = resolve(ExitPrintsService::class)->dispatch(
                        $saveItem->getAttribute('model_id'), (string) $saveItem->getAttribute('item_type'),
                        model: $saveItem, dependsOnId: $result['data']['rest_erp_job_id']
                    );
                    if (! $printResult['success']) {
                        throw new HttpResponseException(response()->json(['error' => $printResult['error']], $printResult['http_status']));
                    }

                    return $data->additional($result['data'])->response()->setStatusCode(
                        $result['http_status'] === 202 || $printResult['http_status'] === 202 ? 202 : 201
                    );
                }

                if ($validatedData['type'] == 'enter') {
                    return $data->additional($returnResult['data'])->response()->setStatusCode(201);
                }

                return $data;
            });
        } catch (HttpResponseException $exception) {
            // Includes broker failures after commit; retain their 503 response and job ID.
            throw $exception;
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    public function customStoreRules(): array
    {
        return [
            'item_type' => 'required|in:0,1,2',
            'vin' => ['required',
                'string',
                'exists:vehicles,vin',
                Rule::unique('gate_permits', 'model_id')->whereNull('deleted_at')
            ],
        ];
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
        } catch (Exception $exception) {
            logger()->error($exception->getMessage());
        }
        return new JsonResponse(
            data: new GatePermitResource($gatePermit)
        );
    }
}
