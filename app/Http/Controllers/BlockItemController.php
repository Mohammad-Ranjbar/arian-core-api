<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportBlockRequest;
use App\Http\Requests\JobRequest;
use App\Http\Resources\BlockItemResource;
use App\Jobs\ExportJob;
use App\Jobs\ImportJob;
use App\Models\Job;
use App\Models\Vehicle;
use App\Repositories\BlockItemRepository;
use App\Services\Erp\ErpWsdlClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BlockItemController extends Controller
{
    /**
     * @param  BlockItemRepository  $repository
     */
    public function __construct(public readonly BlockItemRepository $repository)
    {
    }

    /**
     * @param  Request  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        Request $request,
        Vehicle $vehicle
    ): JsonResponse|AnonymousResourceCollection {
        $this->authorize('block-items.index');
        $perPage = $request->get('per_page', 15);
        return BlockItemResource::collection($vehicle->blockItems()->paginate($perPage));
    }


    /**
     * @param  Request  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|BlockItemResource
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse|BlockItemResource
    {
        try {
            $this->authorize('block-items.store');
            $validatedData = $request->validate($this->repository->getCreateRules());
            $validatedData['blocked_by'] = Auth::id();
            $validatedData['vin'] = $vehicle->vin;

            ErpWsdlClient::run(config('erp.block_unblock'), 'BlockingVehicle', ['barcodeInfoP', $vehicle->vin]);
            return new BlockItemResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    /**
     * @param  Vehicle  $vehicle
     * @param $id
     * @return BlockItemResource|JsonResponse
     */
    public function unblock(Vehicle $vehicle, $id): BlockItemResource|JsonResponse
    {
        try {
            $this->authorize('block-items.unblock');
            $data = [
                'unblocked_by' => Auth::id(), 'unblocked_at' => Carbon::now(),
            ];
            $item = $vehicle->blockItems()->where('block_items.id', $id)->whereNull('unblocked_at')->firstOrFail();
            $item->update($data);

            $exists = $vehicle->blockItems()->whereNull('unblocked_at')->exists();
            if (!$exists) {
                ErpWsdlClient::run(config('erp.block_unblock'), 'UnblockingVehicle', ['barcodeInfoP', $vehicle->vin]);
            }

            return new BlockItemResource($item);

        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    public function report(Request $request): JsonResponse
    {
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'export-block-items';
        $validatedData['data'] = $request->toArray();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));
        return response()->json(
            [
                'message' => 'export job created',
                'data'    => [
                    'uuid'         => $job->getAttribute('uuid'),
                    'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
                ]
            ]
        );
    }

    /**
     * @param  JobRequest  $request
     * @return JsonResponse
     */
    public function importUnblockItems(JobRequest $request): JsonResponse
    {
        if (!isset($this->repository)) {
            return $this->responseError('repository not found', 400);
        }
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-un'.$this->repository->model->getTable();
        $validatedData['data'] = ['file' => $validatedData['file']];
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        $data = [
            'message' => 'import job created',
            'data'    =>
                [
                    'uuid'         => $job->getAttribute('uuid'),
                    'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
                ]
        ];
        return new JsonResponse(
            data: $data,
            status: Response::HTTP_OK
        );
    }

    /**
     * @param  ImportBlockRequest  $request
     * @return JsonResponse
     */
    public function importBlockItems(ImportBlockRequest $request): JsonResponse
    {

        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-'.$this->repository->model->getTable();
        $validatedData['data'] = $validatedData;
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        return new JsonResponse(
            data: [
                'message' => 'import job created',
                'data'    =>
                    [
                        'uuid'         => $job->getAttribute('uuid'),
                        'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
                    ]
            ],
            status: Response::HTTP_OK
        );
    }

}
