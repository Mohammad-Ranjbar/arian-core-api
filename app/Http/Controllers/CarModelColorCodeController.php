<?php

namespace App\Http\Controllers;

use App\Filters\CarModelIdFilter;
use App\Http\Requests\StoreCarModelColorCodeRequest;
use App\Http\Requests\UpdateCarModelColorCodeRequest;
use App\Http\Resources\CarModelColorCodeResource;
use App\Models\CarModelColorCode;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Pipeline;
use function request;

class CarModelColorCodeController extends Controller
{

    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('car-model-color-codes.index');
        $data = Pipeline::send(CarModelColorCode::query())->through([
            CarModelIdFilter::class
        ])->thenReturn()->paginate(request()->input('per_page', 25));

        return CarModelColorCodeResource::collection($data);
    }

    public function store(StoreCarModelColorCodeRequest $request): JsonResponse|CarModelColorCodeResource
    {
        try {
            $data = CarModelColorCode::query()->create($request->validated());

            return CarModelColorCodeResource::make($data);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function show(CarModelColorCode $carModelColorCode): CarModelColorCodeResource
    {
        $this->authorize('car-model-color-codes.show');
        return CarModelColorCodeResource::make($carModelColorCode);
    }

    public function update(UpdateCarModelColorCodeRequest $request, CarModelColorCode $carModelColorCode): JsonResponse|CarModelColorCodeResource
    {
        try {
            $carModelColorCode->update($request->validated());
            $carModelColorCode->refresh();

            return CarModelColorCodeResource::make($carModelColorCode);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(CarModelColorCode $carModelColorCode): Response|JsonResponse
    {
        $this->authorize('car-model-color-codes.destroy');
        try {
            $carModelColorCode->delete();

            return response()->noContent();
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }
}
