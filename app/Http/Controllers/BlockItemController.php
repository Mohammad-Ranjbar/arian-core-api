<?php

namespace App\Http\Controllers;

use App\Events\ErpJobEvent;
use App\Exports\TemplateExport;
use App\Filters\CreatedAtFilter;
use App\Filters\VinFilter;
use App\Http\Requests\BlockItemStoreRequest;
use App\Http\Requests\ImportBlockRequest;
use App\Http\Requests\JobRequest;
use App\Http\Requests\UnblockItemRequest;
use App\Http\Resources\BlockItemResource;
use App\Jobs\ExportJob;
use App\Jobs\ImportJob;
use App\Models\BlockItem;
use App\Models\ErpJob;
use App\Models\Job;
use App\Models\Vehicle;
use App\Repositories\BlockItemRepository;
use App\Repositories\BlockReasonRepository;
use App\Services\Erp\ErpWsdlClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Pipeline;
use Maatwebsite\Excel\Facades\Excel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class BlockItemController extends Controller
{

    public function __construct(public readonly BlockItemRepository $repository, public readonly BlockReasonRepository $reasonRepository)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function systemBlocks(): AnonymousResourceCollection
    {
        $data = Pipeline::send(BlockItem::query()->where('type', 'system')
            ->latest())->through([
            VinFilter::class,
            CreatedAtFilter::class
        ])->thenReturn()->paginate(request()->get('per_page', 30));

        return BlockItemResource::collection($data);
    }

    /**
     * @param Request $request
     * @param Vehicle $vehicle
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        Request $request,
        Vehicle $vehicle
    ): JsonResponse|AnonymousResourceCollection
    {
        $this->authorize('block-items.index');
        $perPage = $request->get('per_page', 15);
        return BlockItemResource::collection($vehicle->blockItems()->where('type', 'user')->latest()->paginate($perPage));
    }


    public function store(BlockItemStoreRequest $request, Vehicle $vehicle): JsonResponse|BlockItemResource
    {
        try {
            $validatedData = $request->validated();
            $record = $this->createBlock($validatedData);
            return new BlockItemResource($record);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    public function createBlock(array $validatedData): Model|Application|\Illuminate\Http\Response|Builder|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        $record = $this->repository->store($validatedData);

        $blockReason = $this->reasonRepository->findBy($validatedData['block_reason_id'], 'id');

        $parameters = [
            'barcodeInfoP' => $record->getAttribute('vin'),
            'blockTypeP' => $blockReason->getAttribute('block_type'),
        ];
        $erpJob = ErpJob::query()->create(
            [
                'queue' => ErpJob::QUEUE_UNIQUE,
                'user_id' => $record->getAttribute('blocked_by'),
                'method' => 'BlockingVehicleByType',
                'url' => config('erp.block_unblock'),
                'parameters' => $parameters,
                'model_type' => BlockItem::class,
                'model_id' => $record->getAttribute('id'),
                'section' => 'block-vehicle',
            ]
        );
        ErpJobEvent::dispatch($erpJob);
        return $record;
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function unblock(Vehicle $vehicle, $id,UnblockItemRequest $request): BlockItemResource|JsonResponse
    {
        try {
            $this->authorize('block-items.unblock');

            $exists = $vehicle->blockItems()->whereNull('unblocked_at')->exists();
            if ($exists) {

                $parameters = [
                    'barcodeInfoP' => $vehicle->getAttribute('vin'),
                    'pDIResultP' => request()->filled('pdi_result') ? request()->get('pdi_result') : '',
                    'pDIDescriptionP' => request()->filled('pdi_result_description') ? request()->get('pdi_result_description') : '',
                    'pDISecondDescriptionP' => request()->filled('pdi_result_description2') ? request()->get('pdi_result_description2') : '',
                ];
//                $erpJob = ErpJob::query()->create([
//                    'model_id' => $id,
//                    'model_type' => BlockItem::class,
//                    'section' => 'unblock-vehicle',
//                    'user_id' => Auth::id(),
//                    'method' => 'UnblockingVehicle',
//                    'url' => config('erp.block_unblock'),
//                    'parameters' => $parameters,
//                    'queue' => ErpJob::QUEUE_UNIQUE
//                ]);
//                ErpJobEvent::dispatch($erpJob);

                $response = ErpWsdlClient::run(config('erp.block_unblock'), 'UnblockingVehicle', $parameters);

                if ($response['status'] !== 'ok') {
                    return $this->responseError($response['message'], 400);
                }

                $data = [
                    'unblocked_by' => Auth::id(),
                    'unblocked_at' => Carbon::now(),
                    'unblock_description' => $request['unblock_description']
                ];
                $item = $vehicle->blockItems()->where('block_items.id', $id)->whereNull('unblocked_at')->firstOrFail();

                $item->update($data);
                $item = $item->fresh();
                return new BlockItemResource($item);
            }

            return $this->responseError('امکان انبلاک وجود ندارد');

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
        return response()->json([
            'message' => 'export job created', 'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

    /**
     * @param JobRequest $request
     * @return JsonResponse
     */
    public function importUnblockItems(JobRequest $request): JsonResponse
    {
        if (!isset($this->repository)) {
            return $this->responseError('repository not found', 400);
        }
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-un' . $this->repository->model->getTable();
        $validatedData['data'] = ['file' => $validatedData['file']];
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        $data = [
            'message' => 'import job created', 'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ];
        return new JsonResponse(data: $data, status: Response::HTTP_OK);
    }

    /**
     * @param ImportBlockRequest $request
     * @return JsonResponse
     */
    public function importBlockItems(ImportBlockRequest $request): JsonResponse
    {

        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-' . $this->repository->model->getTable();
        $validatedData['data'] = $validatedData;
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        return new JsonResponse(data: [
            'message' => 'import job created', 'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ], status: Response::HTTP_OK);
    }

    public function getTemplateUnblockItems(): BinaryFileResponse|JsonResponse
    {
        if (!isset($this->repository)) {
            return $this->responseError('repository not found', 400);
        }
        $headers = ['id'];
        $export = new TemplateExport($headers);
        return Excel::download($export, 'un' . $this->repository->model->getTable() . '-template.xlsx');
    }
}
