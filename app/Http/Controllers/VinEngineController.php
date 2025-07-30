<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportVinEngineRequest;
use App\Http\Requests\JobRequest;
use App\Http\Resources\VinEngineResource;
use App\Jobs\ImportJob;
use App\Models\Job;
use App\Repositories\VinEngineRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VinEngineController extends CrudController
{

    public function __construct(public readonly VinEngineRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(VinEngineResource::class);
    }

    public function customStoreRules(): array
    {
        return [
            'vin' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vin_engines')->where(function ($query) {
                    return $query->where('engine_no', request()->get('engine_no'));
                }),
            ],
            'engine_no' => 'required|string|max:255',
            'production_order_number' => 'nullable|string|max:255',
        ];
    }

    public function customUpdateRules(): array
    {
        return [
            'vin' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vin_engines')->where(function ($query) {
                    return $query->where('engine_no', request()->get('engine_no'));
                })->ignore(request()->route('vin_engine')),
            ],
            'engine_no' => 'required|string|max:255',
            'production_order_number' => 'nullable|string|max:255',
        ];
    }

    public function importer(ImportVinEngineRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-' . $this->repository->model->getTable();
        $validatedData['data'] = $request->all();
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        return response()->json(['message' => 'import job created', 'data' => ['uuid' => $job->getAttribute('uuid'), 'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))]]);
    }
}
