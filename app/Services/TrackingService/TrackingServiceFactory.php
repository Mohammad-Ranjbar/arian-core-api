<?php

namespace App\Services\TrackingService;

class TrackingServiceFactory
{
    public static function getInstance($model)
    {
        if ($model === 'salon') {
            return new SalonTrackingService();
        }
        if ($model === 'production_line') {
            return new ProductionLineTrackingService();
        }
    }

}
