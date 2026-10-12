<?php

namespace App\Http\Controllers;

use App\Http\Resources\RestErpJobResource;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\RestErpJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RestErpJobController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('erp-jobs');
        $filters = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'in-progress', 'success', 'failed', 'uncertain', 'rejected', 'ignored'])],
            'endpoint' => ['sometimes', Rule::in(array_keys(config('rest_erp.endpoints', [])))],
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'section' => ['sometimes', 'string', 'max:255'],
            'request_id' => ['sometimes', 'uuid'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $query = RestErpJob::query()->select([
            'id', 'uuid', 'model_type', 'model_id', 'user_id', 'section', 'service', 'endpoint',
            'execution_mode', 'connection', 'queue', 'status', 'tries', 'execute_time', 'parameters',
            'http_status', 'provider_http_status',
            'error_code', 'description', 'queued_at', 'dispatched_at', 'started_at',
            'completed_at', 'failed_at', 'last_retry_at', 'last_retry_user_id', 'ignored_at',
            'created_at', 'updated_at',
        ]);
        foreach (['status', 'endpoint', 'user_id', 'section'] as $key) {
            if (isset($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if (isset($filters['request_id'])) {
            $query->where('uuid', $filters['request_id']);
        }

        return RestErpJobResource::collection($query->latest('id')->paginate($filters['per_page'] ?? 15));
    }

    public function show(RestErpJob $restErpJob): RestErpJobResource
    {
        $this->authorize('erp-jobs');

        return new RestErpJobResource($restErpJob);
    }

    public function actions(Request $request, RestErpJob $restErpJob)
    {
        $this->authorize('erp-jobs');
        $data = $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);

        return $restErpJob->actionLogs()->oldest('id')->paginate($data['per_page'] ?? 25);
    }

    public function retry(Request $request, RestErpJob $restErpJob, RestErpJobService $service)
    {
        $this->authorize('retry-erp-jobs');
        $data = $request->validate(['acknowledge_uncertain_result' => ['sometimes', 'boolean']]);
        $job = $service->retry($restErpJob, Auth::id(), $data['acknowledge_uncertain_result'] ?? false);

        return (new RestErpJobResource($job))->response()->setStatusCode(202);
    }

    public function ignore(RestErpJob $restErpJob, RestErpJobService $service): RestErpJobResource
    {
        $this->authorize('retry-erp-jobs');

        return new RestErpJobResource($service->ignore($restErpJob, Auth::id()));
    }
}
