<?php

namespace App\Http\Controllers;

use App\Events\ErpJobEvent;
use App\Http\Resources\ErpJobResource;
use App\Models\ErpJob;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ErpJobController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('erp-jobs');
        $data = ErpJob::query()
            ->where('status','failed')
            ->with(['user', 'model'])
            ->latest()
            ->get();
        return ErpJobResource::collection($data);
    }

    /**
     * @throws AuthorizationException
     */
    public function retry(ErpJob $erpJob): JsonResponse
    {
        $this->authorize('retry-erp-jobs');

        $erpJob->load(['user', 'model']);

        if (empty($erpJob->model)) {
            return response()->json([
                'message' => 'model entity of job not found'
            ], 422);
        }
        ErpJobEvent::dispatch($erpJob);

        return response()->json([
            'message' => 'Job retry successfully'
        ]);
    }
}
