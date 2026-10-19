<?php

namespace App\Http\Controllers;

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
use App\Models\Job;
use App\Models\Vehicle;
use App\Repositories\BlockItemRepository;
use App\Repositories\BlockReasonRepository;
use App\Services\Erp\RestApi\VehicleTypeBlockingsService;
use App\Services\Erp\RestApi\VehicleUnblocksService;
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
use RuntimeException;
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
        $blockReason = $this->reasonRepository->findBy($validatedData['block_reason_id'], 'id');

        $result = resolve(VehicleTypeBlockingsService::class)->send(
            $validatedData['vin'],
            $blockReason->getAttribute('block_type'),
            null,
            $validatedData['blocked_by'] ?? null,
        );
        if (!$result['success']) {
            throw new RuntimeException($result['error']['message']);
        }

        return $this->repository->store($validatedData);
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function unblock(Vehicle $vehicle, $id, UnblockItemRequest $request): BlockItemResource|JsonResponse
    {
        try {
            $this->authorize('block-items.unblock');

            $item = $vehicle->blockItems()->where('block_items.id', $id)->first();
            if (! $item) {
                return $this->responseError('رکورد مسدودی مورد نظر برای این خودرو یافت نشد.', 404);
            }
            if ($item->getAttribute('unblocked_at') !== null) {
                return $this->responseError('این رکورد قبلاً رفع مسدودی شده است.', 422);
            }

            $response = resolve(VehicleUnblocksService::class)->send($vehicle->getAttribute('vin'), $item);

            if (! $response['success']) {
                return $this->responseError($response['error']['message'], $response['http_status']);
            }

            $data = [
                'unblocked_by' => Auth::id(),
                'unblocked_at' => Carbon::now(),
                'unblock_description' => $request['unblock_description']
            ];
            $item->update($data);
            $item = $item->fresh();
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

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
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
