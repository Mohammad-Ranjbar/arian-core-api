<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionLineActivityResource;
use App\Jobs\ExportJob;
use App\Models\Job;
use App\Repositories\ProductionLineActivityRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class ProductionLineActivityController extends CrudController
{
    public function __construct(public readonly ProductionLineActivityRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionLineActivityResource::class);
    }

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $perPage = $request->get('per_page', 15);
            if (isset($this->repository)) {
                return $this->collect($this->repository->paginate($perPage, 'id', 'desc'));
            }
            return $this->responseError('repository not found', 400);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    public function report(Request $request): JsonResponse
    {
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'export-production-line-activities';
        $validatedData['data'] = $request->toArray();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));
        return response()->json([
            'message' => 'export job created', 'data' => [
                'uuid'         => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }
}
