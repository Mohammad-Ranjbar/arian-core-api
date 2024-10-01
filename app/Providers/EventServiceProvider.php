<?php

namespace App\Providers;

use App\Models\Salon;
use App\Models\Station;
use App\Models\Tracking;
use App\Models\User;
use App\Models\WorkGroupCenter;
use App\Observers\SalonObserver;
use App\Observers\StationObserver;
use App\Observers\TrackingObserver;
use App\Observers\UserObserver;
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
        User::observe(UserObserver::class);
        Station::observe(StationObserver::class);
        Salon::observe(SalonObserver::class);
        WorkGroupCenter::observe(WorkGroupCenterObserver::class);
        Tracking::observe(TrackingObserver::class);
    }

    /**
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
