<?php

namespace App\Services\TrackingService;

class TrackingModelFactory
{
    /**
     * @param $model
     * @return ProductionLineTrackingModel|SalonTrackingModel|StationTrackingModel|void
     */
    public static function getInstance($model)
    {
        if ($model === 'salon') {
            return new SalonTrackingModel();
        }
        if ($model === 'production_line') {
            return new ProductionLineTrackingModel();
        }
        if ($model === 'station') {
            return new StationTrackingModel();
        }
    }

}