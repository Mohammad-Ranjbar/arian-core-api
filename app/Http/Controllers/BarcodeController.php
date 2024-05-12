<?php

namespace App\Http\Controllers;

use App\Http\Resources\BarcodeResource;
use App\Jobs\ExportJob;
use App\Models\Job;
use App\Repositories\BarcodeRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class BarcodeController extends CrudController
{
    /**
     * @var bool
     */
    protected bool $appendUserId = true;
    public function __construct(public readonly BarcodeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(BarcodeResource::class);
    }

    public function store(Request $request)
    {
        if (! isset($this->repository)) {
            return $this->responseError('repository not found',400);
        }
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(), $this->customStoreRules()));
        $validatedData = array_merge($validatedData, $this->appendStoreData);
        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }

        $submitted = Cache::get('barcode_submitted_' . $validatedData['code']);
        if ($submitted) {
            return new JsonResponse(
                data: ['message' => 'barcode submitted'],
                status: 400
            );
        }
        Cache::set('barcode_submitted_' . $validatedData['code'], true, 10);

        try {
            DB::beginTransaction();
            $saveItem = $this->repository->store($validatedData);
            $saveItem = $this->afterSave($saveItem, $validatedData);
            $data = new $this->resource($saveItem);
            DB::commit();
            return $data;
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    public function afterSave($record, $data)
    {
        $record->updateErpProductItemId();
        return $record;
    }

    public function report(Request $request): JsonResponse
    {
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'export-barcodes';
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
