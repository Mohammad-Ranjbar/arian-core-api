<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserToStationsRequest;
use App\Http\Resources\StationResource;
use App\Http\Resources\UserShortResource;
use App\Models\Station;
use App\Models\User;
use App\Repositories\StationRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
     * @throws AuthorizationException
     */
    public function assignUser(Station $station, AddUserToStationsRequest $request): JsonResponse
    {
        $this->authorize('station-assign-user');
        $validatedData = $request->validated();
        try {
            if ($station->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران ایستگاه کاری انتخاب شده موجود است', 400);
            }
            $station->users()->attach($validatedData);
            return response()->json(new StationResource($station));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AuthorizationException
     */
    public function users(Station $station): AnonymousResourceCollection
    {

        $this->authorize('station.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($station->users()->paginate($perPage));
    }

    /**
     * @param Station $station
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeUser(Station $station, User $user): JsonResponse
    {
        $this->authorize('station-remove-user');
        try {
            $station->users()->detach($user->getAttribute('id'));

            return response()->json(new StationResource($station));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }

    }


}
