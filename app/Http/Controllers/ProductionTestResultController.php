<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductionTestResultRequest;
use App\Http\Resources\ProductionTestResultResource;
use App\Jobs\ExportJob;
use App\Models\Job;
use App\Repositories\ProductionTestResultRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductionTestResultController extends CrudController
{

    protected bool $appendUserId = true;

    public function __construct(public readonly ProductionTestResultRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionTestResultResource::class);
    }

    public function reportExcel(ProductionTestResultRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['type'] = 'export-production-test-results';
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

}
