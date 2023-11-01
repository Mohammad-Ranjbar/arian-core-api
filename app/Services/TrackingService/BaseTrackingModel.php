<?php

namespace App\Services\TrackingService;

use App\Models\Tracking;

abstract class BaseTrackingModel implements TrackingModelInterface
{
    public function getLatestRecord($modelType, $modeId, $vin)
    {
        $where = [
            'model_type' => $modelType,
            'model_id'   => $modeId,
            'vin'        => $vin,
        ];
        return Tracking::query()->where($where)->orderByDesc('id')->first();

    }

}