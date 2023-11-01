<?php

namespace App\Services\TrackingService;

class TrackingModelFactory
{
    public static function getInstance($model)
    {
        if ($model === 'salon') {
            return new SalonTrackingModel();
        }
        //TODO
        if ($model === 'production_line') {
            return new ProductionLineTrackingModel();
        }

    }

}