<?php

namespace App\Providers;

use App\Models\ProductionLine;
use App\Models\Salon;
use App\Models\Station;
use App\Models\User;
use App\Models\VehicleIncident;
use App\Models\WorkGroupCenter;
use App\Observers\ProductionLineObserver;
use App\Observers\SalonObserver;
use App\Observers\StationObserver;
use App\Observers\UserObserver;
use App\Observers\VehicleIncidentObserver;
use App\Observers\WorkGroupCenterObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    /**
     * @var array[]
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];


    /**
     * @return void
     */
    public function boot(): void
    {
        VehicleIncident::observe(VehicleIncidentObserver::class);
        User::observe(UserObserver::class);
        Station::observe(StationObserver::class);
        Salon::observe(SalonObserver::class);
        ProductionLine::observe(ProductionLineObserver::class);
        WorkGroupCenter::observe(WorkGroupCenterObserver::class);
    }

    /**
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
