<?php

namespace App\Http\Controllers;

use App\Filters\TypeFilter;
use App\Http\Requests\AddUsersToProductionLineRequest;
use App\Http\Resources\ProductionLineResource;
use App\Http\Resources\TrackingResource;
use App\Http\Resources\UserShortResource;
use App\Models\ProductionLine;
use App\Models\User;
use App\Repositories\ProductionLineRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProductionLineController extends CrudController
{
    /**
     * @param  ProductionLineRepository  $repository
     */
    public function __construct(public readonly ProductionLineRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionLineResource::class);
    }


    /**
     * @param  ProductionLine  $productionLine
     * @param  AddUsersToProductionLineRequest  $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function assignUsers(
        ProductionLine $productionLine,
        AddUsersToProductionLineRequest $request
    ): JsonResponse {
        $this->authorize('production-line.assign.user');
        $validatedData = $request->validated();
        try {
            if ($productionLine->getAttribute('users')->contains($validatedData['user_id'])) {
                return $this->responseError('کاربر انتخابی در لیست کاربران خط تولید انتخاب شده موجود است', 400);
            }
            $productionLine->users()->attach($validatedData);
            return response()->json(new ProductionLineResource($productionLine));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }


    /**
     * @param  ProductionLine  $productionLine
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function users(ProductionLine $productionLine): AnonymousResourceCollection
    {
        $this->authorize('production-line.users');
        $perPage = request()->get('per_page', 15);

        return UserShortResource::collection($productionLine->users()->paginate($perPage));
    }


    /**
     * @param  ProductionLine  $productionLine
     * @param  User  $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeUser(ProductionLine $productionLine, User $user): JsonResponse
    {
        $this->authorize('production-line.remove.user');
        try {
            $productionLine->users()->detach($user->getAttribute('id'));

            return response()->json(new ProductionLineResource($productionLine));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

    /**
     * @param  ProductionLine  $productionLine
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function tracking(ProductionLine $productionLine): AnonymousResourceCollection
    {
        $this->authorize('production-line.tracking');

        $data = Pipeline::send($productionLine->tracking()->orderByDesc('id'))
            ->through(TypeFilter::class)
            ->thenReturn()
            ->paginate(request()->get('per_page', 15));

        return TrackingResource::collection($data);
    }

}
