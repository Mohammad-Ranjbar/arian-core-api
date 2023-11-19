<?php

namespace App\Http\Controllers;

use App\Http\Resources\BillOfLadingResource;
use App\Repositories\BillOfLadingRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillOfLadingController extends CrudController
{
    /**
     * @var bool
     */
    protected bool $appendUserId = true;

    /**
     * @param  BillOfLadingRepository  $repository
     */
    public function __construct(BillOfLadingRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(BillOfLadingResource::class);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }
        $validatedData['status'] = 'new';
        try {
            DB::beginTransaction();
            $saveItem = $this->repository->store($validatedData);
            $saveItem = $this->afterSave($saveItem, $validatedData);
            $data = new $this->resource($saveItem);
            DB::commit();
            return $data;
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->responseError($exception->getMessage(), 500);
        }
    }
}
