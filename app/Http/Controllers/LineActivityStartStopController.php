<?php

namespace App\Http\Controllers;

use App\Http\Requests\StopLineRequest;
use App\Http\Resources\ProductionLineActivityResource;
use App\Models\ProductionLine;
use App\Models\ProductionLineActivity;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LineActivityStartStopController extends Controller
{

    public function stop(
        StopLineRequest $request,
        ProductionLine $productionLine
    ): JsonResponse|ProductionLineActivityResource {
        $validatedData = $request->validated();
        if ($productionLine->isStop()) {
            return response()->json([
                'message' => 'can not stop the line'
            ], 422);
        }
        $activity = $productionLine->activities()->create($validatedData);
        $activity->logs()->create([
            'action' => 'stop', 'user_id' => Auth::id(),
        ]);
        return new ProductionLineActivityResource($activity);
    }


    /**
     * @param  ProductionLine  $productionLine
     * @return JsonResponse|ProductionLineActivityResource
     * @throws AuthorizationException
     */
    public function start(
        ProductionLine $productionLine
    ): JsonResponse|ProductionLineActivityResource {
        $this->authorize('production-lines.start');
        if ($productionLine->isStarted()) {
            return response()->json([
                'message' => 'can not start the line'
            ], 422);
        }

        $activity = $productionLine->getLatestActivity();
        $activity->logs()->create([
            'action' => 'start', 'user_id' => Auth::id(),
        ]);
        return new ProductionLineActivityResource($activity);
    }


    /**
     * @param  ProductionLine  $productionLine
     * @return JsonResponse|ProductionLineActivityResource
     * @throws AuthorizationException
     */
    public function status(
        ProductionLine $productionLine
    ): JsonResponse|ProductionLineActivityResource {
        $this->authorize('production-lines.status');
        $stop = $productionLine->isStop();
        return response()->json([
            'status' => $stop ? 'stop' : 'start',
            'data'   => $stop ? new ProductionLineActivityResource($productionLine->getLatestActivity()) : null
        ]);
    }

    public function approve(ProductionLineActivity $productionLineActivity): bool|JsonResponse
    {
        try {
            $this->authorize('production-line-activities.approve');
            if ($productionLineActivity->isApproved()) {
                return response()->json([
                    'message' => 'can not approve the activity'
                ], 422);
            }
            $productionLineActivity->logs()->create([
                'action' => 'approved', 'user_id' => Auth::id(),
            ]);
            return true;
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }

    }
}
