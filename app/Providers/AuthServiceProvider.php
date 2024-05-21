<?php

namespace App\Providers;

use App\Models\AccompanyingItem;
use App\Models\Barcode;
use App\Models\Basket;
use App\Models\BillOfLading;
use App\Models\BlockItem;
use App\Models\BlockReason;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Container;
use App\Models\Driver;
use App\Models\Element;
use App\Models\File;
use App\Models\GatePermit;
use App\Models\Incident;
use App\Models\Inspection;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\LocationAttribute;
use App\Models\MakeCode;
use App\Models\ProductionLine;
use App\Models\ProductionLineActivity;
use App\Models\Role;
use App\Models\SaleCompany;
use App\Models\SaleLocation;
use App\Models\Salon;
use App\Models\SmsService;
use App\Models\Station;
use App\Models\StopFact;
use App\Models\StopReason;
use App\Models\StopType;
use App\Models\TransitCompany;
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
        Role::class => CrudPolicy::class,
        MakeCode::class => CrudPolicy::class,
        CarModel::class => CrudPolicy::class,
        User::class => CrudPolicy::class,
        Salon::class => CrudPolicy::class,
        ProductionLine::class => CrudPolicy::class,
        WorkGroupCenter::class => CrudPolicy::class,
        Station::class => CrudPolicy::class,
        Incident::class => CrudPolicy::class,
        Element::class => CrudPolicy::class,
        BlockReason::class => CrudPolicy::class,
        BlockItem::class => CrudPolicy::class,
        TransitCompany::class => CrudPolicy::class,
        Car::class => CrudPolicy::class,
        SmsService::class => CrudPolicy::class,
        StopFact::class => CrudPolicy::class,
        StopReason::class => CrudPolicy::class,
        StopType::class => CrudPolicy::class,
        ProductionLineActivity::class => CrudPolicy::class,
        Barcode::class => CrudPolicy::class,
        Basket::class => CrudPolicy::class,
        Inventory::class => CrudPolicy::class,
        Container::class => CrudPolicy::class,
        GatePermit::class => CrudPolicy::class,
        BillOfLading::class => CrudPolicy::class,
        Driver::class => CrudPolicy::class,
        File::class => CrudPolicy::class,
        SaleLocation::class => CrudPolicy::class,
        AccompanyingItem::class => CrudPolicy::class,
        Location::class => CrudPolicy::class,
        LocationAttribute::class => CrudPolicy::class,
        Inspection::class => CrudPolicy::class,
        SaleCompany::class=>CrudPolicy::class,
        Vehicle::class => VehiclePolicy::class,
    ];


    /**
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
