<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserToStationsRequest;
use App\Http\Resources\StationResource;
use App\Http\Resources\UserShortResource;
use App\Models\Station;
use App\Models\User;
use App\Repositories\StationRepository;
use Exception;
use Illuminate\Http\JsonResponse;

class StationController extends CrudController
{
    /**
     * @param StationRepository $repository
     */
    public function __construct(public readonly StationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(StationResource::class);
    }


    /**
     * @param Station $station
     * @param AddUserToStationsRequest $request
     * @return JsonResponse
     */
    public function assignUser(Station $station, AddUserToStationsRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            if ($station->getAttribute('users')->contains($validatedData['user_id'])){
                return $this->responseError('کاربر انتخابی در لیست کاربران ایستگاه کاری انتخاب شده موجود است',400);
            }
            $station->users()->attach($validatedData);
            return response()->json(new StationResource($station));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

    /**
     * @param Station $station
     * @param User $user
     * @return JsonResponse
     */
    public function removeUser(Station $station, User $user): JsonResponse
    {
        try {
            $station->users()->detach($user->getAttribute('id'));

            return response()->json(new StationResource($station));
        }catch (Exception $exception){
            return $this->responseError($exception->getMessage(),400);
        }

    }

    /**
     * @param Station $station
     * @return JsonResponse
     */
    public function users(Station $station): JsonResponse
    {
        return response()->json(UserShortResource::collection($station->getAttribute('users')));
    }


}
