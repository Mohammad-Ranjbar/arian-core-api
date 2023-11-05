<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockItemResource;
use App\Repositories\BlockItemRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlockItemController extends CrudController
{
    /**
     * @param  BlockItemRepository  $repository
     */
    public function __construct(BlockItemRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(BlockItemResource::class);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
        try {
            $validatedData['blocked_by'] = Auth::id();
            DB::beginTransaction();
            $saveItem = $this->repository->store($validatedData);
            $saveItem = $this->afterSave($saveItem, $validatedData);
            $data = new $this->resource($saveItem);
            DB::commit();
            return $data;
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
