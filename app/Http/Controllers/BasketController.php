<?php

namespace App\Http\Controllers;

use App\Http\Resources\BasketResource;
use App\Repositories\BasketRepository;
use App\Services\HttpServiceTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BasketController extends CrudController
{
    use HttpServiceTrait;

    /**
     * @param  BasketRepository  $repository
     */
    public function __construct(BasketRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(BasketResource::class);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse|mixed
     */
    public function store(Request $request): mixed
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
        request()->merge(['code' => $validatedData['code'], 'created_at_from' => Carbon::now()->subMinutes(10)]);
        $collection = $this->repository->get();
        if ($collection->count() > 0) {
            return response()->json([
                'message' => 'cant create this basket!'
            ]);
        }
        try {
            $validatedData['user_id'] = Auth::id();

            $response = $this->checkBasket($validatedData['code']);

            if (!$response) {
                return response()->json([
                    'message' => 'internal server error !'
                ], 500);
            }
            if (!$response->status) {
                return response()->json([
                    'message' => $response->message
                ], 422);
            }


            $saveItem = $this->repository->store($validatedData);
            return new $this->resource($saveItem);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $basket = $this->repository->show($id);
        if ($basket->status == 'new') {
            return response()->json([
                'message' => 'cant update this basket'
            ], 422);
        }
        $response = $this->sendBasketToErp($basket->code);

        if (!$response) {
            return response()->json([
                'message' => 'internal server error !'
            ], 500);
        }
        if (!$response->status) {
            return response()->json([
                'message' => $response->message
            ], 422);
        }
        $basket->submit();

        return true;
    }
}
