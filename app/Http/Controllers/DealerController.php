<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUsersToDealerRequest;
use App\Http\Resources\DealerResource;
use App\Http\Resources\UserShortResource;
use App\Models\Dealer;
use App\Models\User;
use App\Repositories\DealerRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class DealerController extends CrudController
{

    /**
     * @param  DealerRepository  $repository
     */
    public function __construct(public readonly DealerRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(DealerResource::class);
    }


    /**
     * @param  Dealer  $dealer
     * @param  AddUsersToDealerRequest  $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function assignUser(Dealer $dealer, AddUsersToDealerRequest $request): JsonResponse
    {
        $this->authorize('dealer.assign.user');
        $validatedData = $request->validated();
        try {
            if ($dealer->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران نمایندگی انتخاب شده موجود است', 400);
            }
            $dealer->users()->attach($validatedData);
            return response()->json(new DealerResource($dealer));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @param  Dealer  $dealer
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function users(Dealer $dealer): AnonymousResourceCollection
    {
        $this->authorize('dealer.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($dealer->users()->paginate($perPage));
    }


    /**
     * @param  Dealer  $dealer
     * @param  User  $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeUser(Dealer $dealer, User $user): JsonResponse
    {
        $this->authorize('dealer.remove.user');
        try {
            $dealer->users()->detach($user->getAttribute('id'));
            return response()->json(new DealerResource($dealer));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }
}
