<?php

namespace App\Services\TrackingService;

class TrackingServiceFactory
{

    /**
     * @param $model
     * @return ProductionLineTrackingService|SalonTrackingService|StationTrackingService|void
     */
    public static function getInstance($model)
    {
        if ($model === 'salon') {
            return new SalonTrackingService();
        }
        if ($model === 'production_line') {
            return new ProductionLineTrackingService();
        }
        if ($model === 'station') {
            return new StationTrackingService();
        }

    }

}
