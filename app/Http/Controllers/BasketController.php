<?php

namespace App\Http\Controllers;

use App\Http\Resources\BasketResource;
use App\Repositories\BasketRepository;
use App\Services\Erp\ErpWsdlClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BasketController extends CrudController
{

    /**
     * @param  BasketRepository  $repository
     */
    public function __construct(public readonly BasketRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(BasketResource::class);
    }



    public function store(Request $request)
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
        request()->merge(['code' => $validatedData['code'], 'created_at_from' => Carbon::now()->subMinutes(10)]);
        try {
            $validatedData['user_id'] = Auth::id();

            $response = $this->checkBasketBarcode($validatedData['code']);

            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message']);
            }


            $saveItem = $this->repository->store($validatedData);
            return new $this->resource($saveItem);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }


    /**
     * @param  Request  $request
     * @param $id
     * @return JsonResponse|true
     */
    public function update(Request $request, $id): bool|JsonResponse
    {
        $basket = $this->repository->show($id);
        if ($basket->status != 'new') {
            return $this->responseError('cant update this basket');
        }
        $response = $this->sendBasketBarcode($basket->code);

        if ($response['status'] !== 'ok') {
            return $this->responseError($response['message']);
        }
        $basket->submit();

        return true;
    }

    public function checkBasketBarcode($barcode): array
    {
        return ErpWsdlClient::run(config('erp.basket'),'GetValidationItem_No',['barcodeInfoP', $barcode]);
    }

    public function sendBasketBarcode($barcode): array
    {
        return ErpWsdlClient::run(config('erp.basket'),'CreateItemReclassJournal',['barcodeInfoP', $barcode]);
    }
}
