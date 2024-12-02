<?php

namespace App\Http\Controllers;

use App\Http\Resources\PalletTypeResource;
use App\Repositories\PalletTypeRepository;
use Illuminate\Http\JsonResponse;

class PalletTypeController extends CrudController
{

    public function __construct(public readonly PalletTypeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(PalletTypeResource::class);
    }

    public function destroy($id): JsonResponse
    {
        if (!isset($this->repository)) {
            return $this->responseError('repository not found', 400);
        }
        if ($this->repository->findBy($id)->pallets()->count() > 0) {
            return response()->json(['message' => 'امکان حذف آیتم مورد نظر وجود ندارد.'], 403);
        }
        if ($this->repository->destroy($id)) {
            return response()->json(['message' => 'item deleted!']);
        }
        return $this->responseError('item was not deleted', 500);
    }
}
