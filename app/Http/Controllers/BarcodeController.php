<?php

namespace App\Http\Controllers;

use App\Http\Resources\BarcodeResource;
use App\Jobs\ExportJob;
use App\Models\GeneralProductItem;
use App\Models\Job;
use App\Models\Vehicle;
use App\Repositories\BarcodeRepository;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request): JsonResponse |AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        $data = $this->repository;
        if (isset($this->repository)) {
            if (request()->has('section')) {
                if (request()->get('section') == "serial-items"){
                    $data = $data->model->newQuery()->whereNotNull('type');
                }
                if (request()->get('section') == "general-items"){
                    $data = $data->model->newQuery()->whereHas('generalProductItem');
                }

            }
            return $this->collect($data->paginate($perPage));
        }
        return $this->responseError('repository not found',400);
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

        $vehicle = Vehicle::query()->find($validatedData['vin']);
        if (!empty($validatedData['general_product_item_id'])) {
            $generalProductItem = GeneralProductItem::query()->find( $validatedData['general_product_item_id']);

            $validatedData['general_product_item_id'] = $generalProductItem->getAttribute('id');

            if (in_array($vehicle->getAttribute('qc_status'), [24, 84, 85])) {
                return $this->responseError("You Can Not Enter Serial NO. for Items. VIN Has Been Sign Off ");
            }

            if ($generalProductItem->getAttribute('carModels')
                ->where('id',$vehicle->getAttribute('car_model_id'))->count() == 0) {
                return $this->responseError('enter general item code is not valid for car models',400);
            }
            if ($this->repository->model->newQuery()->where('vin',$validatedData['vin'])->where('general_product_item_id',$generalProductItem->getAttribute('id'))->count() > 0) {
                return $this->responseError('for this vin and general product item has been registered code',400);
            }
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
                if (isset($erpMap[$validatedData['type']])) {
                    $response = $this->submitSerialBarcode($validatedData['vin'], $validatedData['code'], $erpMap[$validatedData['type']]);
                    if ($response['status'] !== 'ok') {
                        return $this->responseError($response['message'], 400);
                    }
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

    public function submitSerialBarcode($vin, $barcode, $erpCode): array
    {
        return ErpWsdlClient::run(config('erp.barcode'), 'SetTracking', [
            'vINP' => $vin,
            'barCodeP' => $barcode,
            'itemTypeP' => $erpCode,
        ]);

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
