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

    /**
     * @param BarcodeRepository $repository
     */
    public function __construct(public readonly BarcodeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(BarcodeResource::class);
    }

    /**
     * @param Request $request
     * @return JsonResponse|mixed
     */
    public function store(Request $request): mixed
    {
        if (!isset($this->repository)) {
            return $this->responseError('repository not found', 400);
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


            if (!empty($validatedData['type'])) {
                $erpMap = config('serial_barcodes.erp_map');
                if(isset($erpMap[$validatedData['type']])){
                    // TODO send to erp - vin , $erpMap[$validatedData['type']] barcode
                }
            }


            $saveItem = $this->afterSave($saveItem, $validatedData);
            $data = new $this->resource($saveItem);
            DB::commit();
            return $data;
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    /**
     * @param $record
     * @param $data
     * @return mixed
     */
    public function afterSave($record, $data): mixed
    {
        $record->updateErpProductItemId();
        return $record;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function report(Request $request): JsonResponse
    {
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'export-barcodes';
        $validatedData['data'] = $request->toArray();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));
        return response()->json([
            'message' => 'export job created', 'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

}
