<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitActivityResource;
use App\Repositories\TransitActivityRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransitActivityController extends CrudController
{
    /**
     * @param  TransitActivityRepository  $repository
     */
    public function __construct(TransitActivityRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(TransitActivityResource::class);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
        try {
            $validatedData['user_id'] = Auth::id();
            $saveItem = $this->repository->store($validatedData);
            return new TransitActivityResource($saveItem);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

}
