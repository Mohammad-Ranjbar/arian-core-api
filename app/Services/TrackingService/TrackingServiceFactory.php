<?php

namespace App\Services\TrackingService;

use Illuminate\Validation\ValidationException;

class TrackingServiceFactory
{
    /**
     * @param $model
     * @return InventoryTrackingService|LocationTrackingService|SaleLocationTrackingService|ProductionLineTrackingService|ValidationException|StationTrackingService|SalonTrackingService
     */
    public static function getInstance($model): InventoryTrackingService|LocationTrackingService|SaleLocationTrackingService|ProductionLineTrackingService|ValidationException|StationTrackingService|SalonTrackingService
    {
        return match ($model) {
            'salons'            => new SalonTrackingService(),
            'production_lines'  => new ProductionLineTrackingService(),
            'stations'          => new StationTrackingService(),
            'inventory'         => new InventoryTrackingService(),
            'sale_locations'    => new SaleLocationTrackingService(),
            'locations'         => new LocationTrackingService(),
            default             => ValidationException::withMessages(['error' => 'The input data is not valid']),
        };
    }

}
