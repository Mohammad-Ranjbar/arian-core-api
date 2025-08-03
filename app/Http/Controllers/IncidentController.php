<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportIncidentRequest;
use App\Http\Requests\JobRequest;
use App\Http\Resources\IncidentResource;
use App\Jobs\ExportJob;
use App\Jobs\ImportJob;
use App\Models\Job;
use App\Repositories\IncidentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends CrudController
{
    public function __construct(public readonly IncidentRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(IncidentResource::class);
    }

    public function report(Request $request): JsonResponse
    {

        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'export-incidents';
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

    public function importIncidents(ImportIncidentRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-' . $this->repository->model->getTable();
        $validatedData['data'] = $request->validated();
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        return response()->json(['message' => 'import job created', 'data' => ['uuid' => $job->getAttribute('uuid'), 'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))]]);
    }
}
