<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUsersToLocation;
use App\Http\Resources\LocationResource;
use App\Http\Resources\UserShortResource;
use App\Models\Location;
use App\Models\User;
use App\Repositories\LocationRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LocationController extends CrudController
{

    /**
     * @param  LocationRepository  $repository
     */
    public function __construct(public readonly LocationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(LocationResource::class);
    }

    /**
     * @param $record
     * @param $data
     * @return mixed
     */
    public function afterSave($record, $data): mixed
    {
        $record->attributes()->delete();

        foreach ($data['attributes'] as $attribute => $value) {
            $record->attributes()->create([
                'attribute' => $attribute,
                'value'     => $value,
            ]);
        }
        return $record;
    }


    /**
     * @param  Location  $location
     * @param  AddUsersToLocation  $request
     * @return JsonResponse
     */
    public function assignUser(
        Location $location,
        AddUsersToLocation $request
    ): JsonResponse {
        $validatedData = $request->validated();
        try {
            if ($location->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران لوکیشن انتخاب شده موجود است', 400);
            }
            $location->users()->attach($validatedData);
            return response()->json(new LocationResource($location));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @param  Location  $location
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function users(Location $location): AnonymousResourceCollection
    {
        $this->authorize('location.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($location->users()->paginate($perPage));
    }


    /**
     * @param  Location  $location
     * @param  User  $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeUser(Location $location, User $user): JsonResponse
    {
        $this->authorize('saleLocation.remove.user');
        try {
            $location->users()->detach($user->getAttribute('id'));
            return response()->json(new LocationResource($location));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

}
