<?php

namespace App\Http\Controllers;

use App\Events\ErpJobEvent;
use App\Http\Resources\ErpJobResource;
use App\Models\ErpJob;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ErpJobController extends Controller
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('erp-jobs');
        $data = ErpJob::query()
            ->where('status','failed')
            ->with(['user', 'model'])
            ->latest()
            ->paginate(request()->get('per_page') ?? '15');
        return ErpJobResource::collection($data);
    }

    /**
     * @throws AuthorizationException
     */
    public function retry(ErpJob $erpJob): JsonResponse
    {
        $this->authorize('retry-erp-jobs');

        if ($erpJob->getAttribute('status') !== 'failed') {
            return response()->json([
                'message' => 'Job retry is not failed',
            ]);
        }
        $erpJob->load(['user', 'model']);

//        if (empty($erpJob->model)) {
//            return response()->json([
//                'message' => 'model entity of job not found'
//            ], 422);
//        }

        $erpJob->setStatusInProgress();

        ErpJobEvent::dispatch($erpJob);

        return response()->json([
            'message' => 'Job retry successfully'
        ]);
    }
}
