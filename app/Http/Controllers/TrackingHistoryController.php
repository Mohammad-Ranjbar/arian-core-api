<?php

namespace App\Http\Controllers;

use App\Filters\CreatedAtFromFilter;
use App\Filters\CreatedAtToFilter;
use App\Http\Resources\TrackingResource;
use App\Models\ProductionLine;
use App\Models\Salon;
use App\Models\Station;
use App\Models\Tracking;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;

class TrackingHistoryController extends Controller
{


    /**
     * @param $modelType
     * @param $modelId
     * @param $type
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function __invoke(
        $modelType,
        $modelId,
        $type
    ): JsonResponse|AnonymousResourceCollection {
        $this->authorize('tracking-history');
        $modelType = match ($modelType) {
            'salons' => Salon::class,
            'production_lines' => ProductionLine::class,
            'stations' => Station::class,
            default => '',
        };

        if ($modelType) {
            $whereCondition = [
                ['model_type', $modelType],
                ['model_id', $modelId],
                ['type', $type]
            ];

            $perPage = request()->input('per_page', 15);
            $data = Pipeline::send(Tracking::query()->where($whereCondition))->through([
                CreatedAtToFilter::class,
                CreatedAtFromFilter::class
            ])->thenReturn()->latest()->paginate($perPage);
            return TrackingResource::collection($data);
        } else {
            return $this->responseError('ورودی باید از بین سالن - خط تولید یا ایستگاه های کاری باشد', 400);
        }

    }
}
