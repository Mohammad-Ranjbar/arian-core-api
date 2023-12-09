<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserToStationsRequest;
use App\Http\Resources\StationResource;
use App\Models\Station;
use App\Models\User;
use App\Repositories\StationRepository;
use Exception;
use Illuminate\Http\JsonResponse;

class StationController extends CrudController
{
    public function __construct(public readonly StationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(StationResource::class);
    }


    public function addUser(Station $station, AddUserToStationsRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $station->users()->attach($validatedData);
            return response()->json(new StationResource($station));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

    public function removeUser(Station $station,User $user): JsonResponse
    {
        try {
            $station->users()->detach($user->getAttributes('id'));

            return response()->json(new StationResource($station));
        }catch (Exception $exception){
            return $this->responseError($exception->getMessage(),400);
        }

    }


}
