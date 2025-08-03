<?php

namespace App\Http\Controllers;

use App\Filters\ProductionOrderNumberFilter;
use App\Filters\VinFilter;
use App\Http\Requests\StoreChangeVinRequest;
use App\Http\Resources\ChangeVinResource;
use App\Models\ChangeVin;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;


class ChangeVinController extends Controller
{

    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('change-vins.index');
        $data = Pipeline::send(ChangeVin::query())->through([
            VinFilter::class,
            ProductionOrderNumberFilter::class
        ])->thenReturn()->paginate(request()->input('per_page', 20));

        return ChangeVinResource::collection($data);
    }

    public function store(StoreChangeVinRequest $request): JsonResponse|ChangeVinResource
    {
        try {
            $data = ChangeVin::query()->create($request->validated());
            return ChangeVinResource::make($data);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function sendErp($data)
    {
        //TODO with Meysam
    }
}
