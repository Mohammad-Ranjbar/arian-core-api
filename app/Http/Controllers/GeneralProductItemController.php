<?php

namespace App\Http\Controllers;

use App\Http\Resources\GeneralProductItemResource;
use App\Jobs\ExportJob;
use App\Models\Job;
use App\Models\Vehicle;
use App\Repositories\GeneralProductItemRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class GeneralProductItemController extends CrudController
{
    public function __construct(public readonly GeneralProductItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(GeneralProductItemResource::class);
    }

    public function carModelItems(string $carModelId): JsonResponse
    {
        $items = $this->repository->model->newQuery()->with('carModels')->whereHas('carModels',
            function ($query) use ($carModelId) {
                $query->where('car_model_id', $carModelId);
            })->get();

        return new JsonResponse(
            data: GeneralProductItemResource::collection($items),
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function reportExcel(Request $request): JsonResponse
    {
        $this->authorize('export-general-product-items');
        $validatedData['type'] = 'export-general-product-items';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = request()->all();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'export job created',
            'data'    => [
                'uuid'         => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     */
    public function vehicleItems(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('general-product-items.vehicle');
        $carModelId = $vehicle->getAttribute('car_model_id');

        $items = $this->repository->model->newQuery()
            ->when(request()->get('type'), function ($q) {
                $q->where('type', request()->get('type'));
            })
            ->whereHas('carModels', function ($query) use ($carModelId) {
                $query->where('car_model_id', $carModelId);
            })->orderBy('priority')->get();

        return new JsonResponse(
            data: GeneralProductItemResource::collection($items),
        );
    }
}
