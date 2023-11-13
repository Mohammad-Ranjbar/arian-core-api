<?php

namespace App\Services\TrackingService;

use App\Models\Tracking;
use Illuminate\Database\Eloquent\Model;

abstract class BaseTrackingService implements TrackingServiceInterface
{
    /**
     * @param $modelType
     * @param $modeId
     * @param $vin
     * @return Model|null
     */
    public function getLatestRecord($modelType, $modeId, $vin):Model | null
    {
        $where = [
            'model_type' => $modelType, 'model_id' => $modeId, 'vin' => $vin,
        ];
        return Tracking::query()->where($where)->latest()->first();
    }

}