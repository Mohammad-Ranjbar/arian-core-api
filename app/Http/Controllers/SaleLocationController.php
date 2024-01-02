<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUsersToSaleLocationRequest;
use App\Http\Resources\SaleLocationResource;
use App\Http\Resources\UserShortResource;
use App\Models\SaleLocation;
use App\Models\User;
use App\Repositories\SaleLocationRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SaleLocationController extends CrudController
{

    /**
     * @param  SaleLocationRepository  $repository
     */
    public function __construct(public readonly SaleLocationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SaleLocationResource::class);
    }


    /**
     * @param  SaleLocation  $saleLocation
     * @param  AddUsersToSaleLocationRequest  $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function assignUser(
        SaleLocation $saleLocation,
        AddUsersToSaleLocationRequest $request
    ): JsonResponse {
        $this->authorize('saleLocation.assign.user');
        $validatedData = $request->validated();
        try {
            if ($saleLocation->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران انبار فروش انتخاب شده موجود است', 400);
            }
            $saleLocation->users()->attach($validatedData);
            return response()->json(new SaleLocationResource($saleLocation));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @param  SaleLocation  $saleLocation
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function users(SaleLocation $saleLocation): AnonymousResourceCollection
    {
        $this->authorize('saleLocation.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($saleLocation->users()->paginate($perPage));
    }


    /**
     * @param  SaleLocation  $saleLocation
     * @param  User  $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeUser(SaleLocation $saleLocation, User $user): JsonResponse
    {
        $this->authorize('saleLocation.remove.user');
        try {
            $saleLocation->users()->detach($user->getAttribute('id'));
            return response()->json(new SaleLocationResource($saleLocation));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }
}
