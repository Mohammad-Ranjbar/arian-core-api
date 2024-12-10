<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserToStationsRequest;
use App\Http\Resources\StopFactResource;
use App\Http\Resources\UserGroupResource;
use App\Http\Resources\UserShortResource;
use App\Models\StopFact;
use App\Models\User;
use App\Models\UserGroup;
use App\Repositories\UserGroupRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserGroupController extends CrudController
{

    public function __construct(public readonly UserGroupRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(UserGroupResource::class);
    }

    public function assignUser(UserGroup $userGroup, AddUserToStationsRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            if ($userGroup->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران انتخاب شده موجود است', 400);
            }

            $userGroup->users()->attach($validatedData['user_id']);
            return response()->json(new UserGroupResource($userGroup));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(),500);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AuthorizationException
     */
    public function users(UserGroup $userGroup): AnonymousResourceCollection
    {

        $this->authorize('user-group.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($userGroup->users()->paginate($perPage));
    }


    /**
     * @throws AuthorizationException
     */
    public function removeUser(UserGroup $userGroup, User $user): JsonResponse
    {
        $this->authorize('user-group.remove.user');
        try {
            $userGroup->users()->detach($user->getAttribute('id'));

            return response()->json(new UserGroupResource($userGroup));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }

    }

}
