<?php

namespace App\Providers;

use App\Models\BlockItem;
use App\Models\BlockReason;
use App\Models\CarModel;
use App\Models\Element;
use App\Models\Incident;
use App\Models\MakeCode;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Salon;
use App\Models\SmsService;
use App\Models\Station;
use App\Models\StopFact;
use App\Models\StopReason;
use App\Models\TransitActivity;
use App\Models\TransitCompany;
use App\Models\TransitDestination;
use App\Models\TransitDriver;
use App\Models\TransitSource;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkGroupCenter;
use App\Policies\CrudPolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

    /**
     * @var string[]
     */
    protected $policies = [
        Role::class               => CrudPolicy::class,
        MakeCode::class           => CrudPolicy::class,
        CarModel::class           => CrudPolicy::class,
        User::class               => CrudPolicy::class,
        Salon::class              => CrudPolicy::class,
        ProductionLine::class     => CrudPolicy::class,
        WorkGroupCenter::class    => CrudPolicy::class,
        Station::class            => CrudPolicy::class,
        Incident::class           => CrudPolicy::class,
        Element::class            => CrudPolicy::class,
        BlockReason::class        => CrudPolicy::class,
        BlockItem::class          => CrudPolicy::class,
        TransitSource::class      => CrudPolicy::class,
        TransitDestination::class => CrudPolicy::class,
        TransitCompany::class     => CrudPolicy::class,
        TransitActivity::class    => CrudPolicy::class,
        TransitDriver::class      => CrudPolicy::class,
        SmsService::class         => CrudPolicy::class,
        StopFact::class           => CrudPolicy::class,
        StopReason::class         => CrudPolicy::class,

        Vehicle::class => VehiclePolicy::class
    ];


    /**
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
