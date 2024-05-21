<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUsersToSaleCompanyRequest;
use App\Http\Resources\SaleCompanyResource;
use App\Http\Resources\UserShortResource;
use App\Models\SaleCompany;
use App\Models\User;
use App\Repositories\SaleCompanyRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SaleCompanyController extends CrudController
{

    /**
     * @param SaleCompanyRepository $repository
     */
    public function __construct(public readonly SaleCompanyRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SaleCompanyResource::class);
    }


    /**
     * @param SaleCompany $saleCompany
     * @param AddUsersToSaleCompanyRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function assignUser(
        SaleCompany                  $saleCompany,
        AddUsersToSaleCompanyRequest $request
    ): JsonResponse
    {
        $this->authorize('sale-company.assign.user');
        $validatedData = $request->validated();
        try {
            if ($saleCompany->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران انتخاب شده موجود است', 400);
            }
            $saleCompany->users()->attach($validatedData);
            return response()->json(new SaleCompanyResource($saleCompany));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @param SaleCompany $saleCompany
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function users(SaleCompany $saleCompany): AnonymousResourceCollection
    {
        $this->authorize('sale-company.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($saleCompany->users()->paginate($perPage));
    }


    /**
     * @param SaleCompany $saleCompany
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeUser(SaleCompany $saleCompany, User $user): JsonResponse
    {
        $this->authorize('sale-company.remove.user');
        try {
            $saleCompany->users()->detach($user->getAttribute('id'));

            return response()->json(new SaleCompanyResource($saleCompany));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }
}
