<?php

namespace App\Providers;

use App\Events\ErpJobEvent;
use App\Listeners\SendErpJobListener;
use App\Models\EnterEngine;
use App\Models\ErpJob;
use App\Models\MainItemShortage;
use App\Models\RelatedItemShortage;
use App\Models\SpecificShortageItem;
use App\Models\Tracking;
use App\Models\User;
use App\Observers\EnterEngineObserver;
use App\Observers\ErpJobObserver;
use App\Observers\MainItemShortageObserver;
use App\Observers\RelatedItemShortageObserver;
use App\Observers\SpecificShortageItemObserver;
use App\Observers\TrackingObserver;
use App\Observers\UserObserver;
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
        ErpJobEvent::class => [
            SendErpJobListener::class,
        ],
    ];

    public function boot(): void
    {
        User::observe(UserObserver::class);
        EnterEngine::observe(EnterEngineObserver::class);
        ErpJob::observe(ErpJobObserver::class);
        Tracking::observe(TrackingObserver::class);
        SpecificShortageItem::observe(SpecificShortageItemObserver::class);
        MainItemShortage::observe(MainItemShortageObserver::class);
        RelatedItemShortage::observe(RelatedItemShortageObserver::class);
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
