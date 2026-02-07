<?php

namespace App\Http\Controllers;

use App\Events\ErpJobEvent;
use App\Http\Resources\ErpJobResource;
use App\Models\ErpJob;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        $data = ErpJob::query();

        $data = request()->has('status') && request()->get('status') == 'ignore' ?
            $data->where('status', 'ignore') :
            $data->where('status', 'failed');
        $data = $data->with(['user', 'model'])
            ->latest()
            ->paginate(request()->get('per_page') ?? '15');
        return ErpJobResource::collection($data);
    }


    /**
     * @throws \Throwable
     */
    public function retry(ErpJob $erpJob): JsonResponse
    {
        $lockKey = 'erp_job_retry_' . $erpJob->getAttribute('id');
        $lock = Cache::lock($lockKey, 15);

        if (!$lock->get()) {
            return new JsonResponse(
                data: ['message' => 'در حال حاضر درخواست در حال پردازش است. لطفا صبور باشید.'],
                status: 429
            );
        }

        try {
            return DB::transaction(function () use ($erpJob) {
                $this->authorize('retry-erp-jobs');

                $freshJob = $erpJob->fresh();

                if ($freshJob->getAttribute('status') !== 'failed') {
                    throw new Exception('Job retry is not failed');
                }

                $freshJob->load(['user', 'model']);

                $freshJob->setStatusStart();

                ErpJobEvent::dispatch($freshJob);

                return response()->json([
                    'message' => 'Job retry successfully'
                ]);
            });
        } catch (Exception $exception) {
            $erpJob->update(['status' => 'failed']);
            return $this->responseError($exception->getMessage());
        } finally {
            $lock->release();
        }
    }

    public function changeStatusToIgnore(ErpJob $erpJob): JsonResponse
    {
        try {
            if ($erpJob->getAttribute('status') == 'ignore') {
                return $this->responseError('Job status is already ignore', 400);
            }
            $erpJob->update(['status' => 'ignore']);
            return response()->json([
                'message' => 'Job status successfully ignored',
            ]);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }
}
