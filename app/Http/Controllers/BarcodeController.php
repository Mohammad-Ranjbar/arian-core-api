<?php

namespace App\Http\Controllers;

use App\Http\Resources\BarcodeResource;
use App\Repositories\BarcodeRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarcodeController extends CrudController
{
    public function __construct(public readonly BarcodeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(BarcodeResource::class);
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
            $saveItem->updateErpProductItemId();
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
