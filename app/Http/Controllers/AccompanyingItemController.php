<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCarModelToAccompanyingItem;
use App\Http\Resources\AccompanyingItemResource;
use App\Http\Resources\CarModelResource;
use App\Models\AccompanyingItem;
use App\Models\CarModel;
use App\Repositories\AccompanyingItemRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AccompanyingItemController extends CrudController
{

    /**
     * @param  AccompanyingItemRepository  $repository
     */
    public function __construct(public readonly AccompanyingItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AccompanyingItemResource::class);
    }


    /**
     * @param  AccompanyingItem  $accompanyingItem
     * @param  AddCarModelToAccompanyingItem  $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function assignCarModel(
        AccompanyingItem $accompanyingItem,
        AddCarModelToAccompanyingItem $request
    ): JsonResponse {
        $this->authorize('accompanying-item.assign.carModel');
        $validatedData = $request->validated();
        try {
            if ($accompanyingItem->getAttribute('carModels')->contains($validatedData['car_model_id'])) {
                return $this->responseError('کارمدل انتخابی در لیست کارمدل های اقلام جانبی انتخاب شده موجود است', 400);
            }
            $accompanyingItem->carModels()->attach($validatedData);
            return response()->json(new AccompanyingItemResource($accompanyingItem));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

    /**
     * @param  AccompanyingItem  $accompanyingItem
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function carModels(AccompanyingItem $accompanyingItem
    ): AnonymousResourceCollection {
        $this->authorize('accompanying-item.carModels');
        $perPage = request()->get('per_page', 15);

        return CarModelResource::collection($accompanyingItem->carModels()->paginate($perPage));
    }

    /**
     * @param  AccompanyingItem  $accompanyingItem
     * @param  CarModel  $carModel
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeCarModel(AccompanyingItem $accompanyingItem, CarModel $carModel): JsonResponse
    {
        $this->authorize('accompanying-item.remove.carModel');
        try {
            $accompanyingItem->carModels()->detach($carModel->getAttribute('id'));
            return response()->json(new AccompanyingItemResource($accompanyingItem));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }
}
