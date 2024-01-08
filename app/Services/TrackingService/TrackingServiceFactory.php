<?php

namespace App\Services\TrackingService;

use ErrorException;

class TrackingServiceFactory
{


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
        if ($model === 'inventory') {
            return new InventoryTrackingService();
        }
        if ($model === 'sale_locations') {
            return new SaleLocationTrackingService();
        }
        if ($model === 'locations') {
            return new LocationTrackingService();
        }

    }

}
