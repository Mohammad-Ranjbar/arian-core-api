<?php

namespace App\Http\Controllers;

use App\Http\Resources\RelatedItemShortageResource;
use App\Models\MainItemShortage;
use App\Repositories\RelatedItemShortageRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RelatedItemShortageController extends CrudController
{

    public function __construct(public readonly RelatedItemShortageRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(RelatedItemShortageResource::class);
    }

    /**
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        if (!isset($this->repository)) {
            return $this->responseError('repository not found', 400);
        }
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(), $this->customStoreRules()));
        $validatedData = array_merge($validatedData, $this->appendStoreData);
        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }
        try {
            DB::beginTransaction();
            if (filled($validatedData['location_code'])) {
                MainItemShortage::query()->where('id',$validatedData['main_item_shortage_id'])
                    ->first()->update([
                        'location_code' => $validatedData['location_code'],
                    ]);
            }
            if (filled($validatedData['item_no'])){
                unset($validatedData['location_code']);
                $saveItem = $this->repository->store($validatedData);
            }

            DB::commit();

            return new $this->resource($saveItem);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
