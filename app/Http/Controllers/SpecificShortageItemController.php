<?php

namespace App\Http\Controllers;

use App\Http\Resources\SpecificShortageItemResource;
use App\Repositories\SpecificShortageItemRepository;

class SpecificShortageItemController extends CrudController
{
    public function __construct(public readonly SpecificShortageItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SpecificShortageItemResource::class);
    }

    public function destroy($id)
    {
        if(! $this->repository->findBy($id,'id')->getAttribute('canDelete')){
            return response()->json('امکان حذف ایتم وجود ندارد', 400);
        }
        if ($this->repository->destroy($id)) {
            return response()->json(['message' => 'item deleted!']);
        }
        return $this->responseError('امکان حذف آیتم مورد نظر وجود ندارد.');
    }
}
