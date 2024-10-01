<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserToStationsRequest;
use App\Http\Resources\StopFactResource;
use App\Http\Resources\UserShortResource;
use App\Models\StopFact;
use App\Models\User;
use App\Repositories\StopFactRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class StopFactController extends CrudController
{
    public function __construct(public readonly StopFactRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(StopFactResource::class);
    }

    /**
     * @throws AuthorizationException
     */
    public function assignUser(StopFact $stopFact, AddUserToStationsRequest $request): JsonResponse
    {
        $this->authorize('stop-fact.assign.user');
        $validatedData = $request->validated();
        try {
            if ($stopFact->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران انتخاب شده موجود است', 400);
            }

            $stopFact->users()->attach($validatedData['user_id']);
            return response()->json(new StopFactResource($stopFact));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(),500);
        }
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AuthorizationException
     */
    public function users(StopFact $stopFact): AnonymousResourceCollection
    {

        $this->authorize('stop-fact.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($stopFact->users()->paginate($perPage));
    }


    /**
     * @throws AuthorizationException
     */
    public function removeUser(StopFact $stopFact, User $user): JsonResponse
    {
        $this->authorize('stop-fact.remove.user');
        try {
            $stopFact->users()->detach($user->getAttribute('id'));

            return response()->json(new StopFactResource($stopFact));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }

    }
}
