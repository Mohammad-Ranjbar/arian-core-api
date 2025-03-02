<?php

namespace App\Http\Controllers;

use App\Events\ErpJobEvent;
use App\Http\Requests\UpdateBasketBatchRequest;
use App\Http\Resources\BasketResource;
use App\Models\Basket;
use App\Models\ErpJob;
use App\Repositories\BasketRepository;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BasketController extends CrudController
{

    /**
     * @param BasketRepository $repository
     */
    public function __construct(public readonly BasketRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(BasketResource::class);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate($this->repository->getCreateRules());

        $submitted = Cache::get('basket_barcode_submitted_' . $validatedData['code']);
        if ($submitted) {
            return new JsonResponse(
                data: ['message' => 'barcode submitted'],
                status: 400
            );
        }
        Cache::set('basket_barcode_submitted_' . $validatedData['code'], true, 60 * 10);

        try {
            $validatedData['user_id'] = Auth::id();

            $response = $this->checkBasketBarcode($validatedData['code']);

            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 500);
            }

            $validatedData['description'] = $response['data'];
            $saveItem = $this->repository->store($validatedData);
            return new $this->resource($saveItem);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    public function checkBasketBarcode($barcode): array
    {
        return ErpWsdlClient::run(config('erp.basket'), 'GetValidationItem_No', [
            'barcodeInfoP' => $barcode
        ]);
    }

    public function batchUpdate(UpdateBasketBatchRequest $request): true|JsonResponse
    {
        $validatedData = $request->validated();
        $lock = Cache::lock('batch_update_process_' . Auth::id(), 10);
        if ($lock->get()) {
            $baskets = Basket::query()->where('user_id', Auth::id())->where('status', 'new')->get();
            if ($baskets->count() == 0) {
                return response()->json([
                    'message' => 'بارکدی وجود ندارد'
                ]);
            }
            $this->sendBasketBatchBarcode($baskets->pluck('code')->toArray(), $validatedData['batch'],
                $baskets->first()->id);
            Basket::query()->whereIn('id', $baskets->pluck('id')->toArray())->update(['status' => 'submitted']);
            $lock->release();
        }else {
            return response()->json('Server busy',500);
        }

        return true;
    }

    public function sendBasketBatchBarcode(array $codes, $batch, $basketId): void
    {
        $parameters = [
            'numberP' => count($codes),
            'arrayCrossReferenceNoP' => implode(",", $codes),
            'batchNoP' => 'Batch-' . $batch
        ];
        $erpJob = ErpJob::query()->create([
            'model_id' => $basketId,
            'model_type' => Basket::class,
            'section' => 'send-basket',
            'user_id' => Auth::id(),
            'method' => 'GroupItemReclassJournal',
            'url' => config('erp.basket_batch'),
            'parameters' => $parameters,
            'queue' => ErpJob::QUEUE_UNIQUE
        ]);

        ErpJobEvent::dispatch($erpJob);

    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse|true
     */
    public function update(Request $request, $id): bool|JsonResponse
    {
        $basket = $this->repository->show($id);
        if ($basket->status != 'new') {
            return $this->responseError('cant update this basket');
        }
        $this->sendBasketBarcode($basket, $request->get('batch'));


        $basket->submit();

        return true;
    }

    public function sendBasketBarcode(Basket $basket, $batch): void
    {
        $parameters = [
            'barcodeInfoP' => $basket->getAttribute('code'),
            'batchNoP' => 'Batch-' . $batch
        ];
        $erpJob = ErpJob::query()->create([
            'model_id' => $basket->getAttribute('id'),
            'model_type' => Basket::class,
            'section' => 'send-basket',
            'user_id' => Auth::id(),
            'method' => 'CreateItemReclassJournal',
            'url' => config('erp.basket'),
            'parameters' => $parameters,
            'queue' => ErpJob::QUEUE_UNIQUE
        ]);

        ErpJobEvent::dispatch($erpJob);

    }
}
