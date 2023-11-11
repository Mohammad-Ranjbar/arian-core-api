<?php

namespace App\Http\Controllers;

use App\Http\Resources\BasketResource;
use App\Repositories\BasketRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BasketController extends CrudController
{
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
        try {
            $validatedData['user_id'] = Auth::id();
            $saveItem = $this->repository->store($validatedData);
            return new $this->resource($saveItem);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @param  Request  $request
     * @param $id
     * @return JsonResponse|mixed
     */
    public function update(Request $request, $id): mixed
    {
        $validatedData = $request->validate($this->repository->getUpdateRules());
        try {
            $validatedData['user_id'] = Auth::id();
            $model = $this->repository->update($id, $validatedData);
            return new $this->resource($model);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
