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
        if ($model === 'salons') {
            return new SalonTrackingService();
        }
        if ($model === 'production_lines') {
            return new ProductionLineTrackingService();
        }
        if ($model === 'stations') {
            return new StationTrackingService();
        }

    }

}
