<?php

namespace App\Providers;

use App\Models\AccompanyingItem;
use App\Models\Barcode;
use App\Models\Basket;
use App\Models\BillOfLading;
use App\Models\BlockItem;
use App\Models\BlockReason;
use App\Models\BodyOption;
use App\Models\CabinNumber;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\ColorMap;
use App\Models\Container;
use App\Models\CopPlan;
use App\Models\Driver;
use App\Models\Element;
use App\Models\File;
use App\Models\GatePermit;
use App\Models\GeneralProductItem;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Inspection;
use App\Models\Inventory;
use App\Models\ItemInputChecklist;
use App\Models\ItemTechnicalChecklist;
use App\Models\Location;
use App\Models\LocationAttribute;
use App\Models\MakeCode;
use App\Models\Pallet;
use App\Models\PalletType;
use App\Models\ProductBom;
use App\Models\ProductionChecklist;
use App\Models\ProductionLine;
use App\Models\ProductionLineActivity;
use App\Models\ProductionTest;
use App\Models\ProductionTestResult;
use App\Models\QcCode;
use App\Models\Role;
use App\Models\SaleCompany;
use App\Models\SaleInvoice;
use App\Models\SaleLocation;
use App\Models\Salon;
use App\Models\Screw;
use App\Models\Setting;
use App\Models\ShortageItem;
use App\Models\SmsService;
use App\Models\Station;
use App\Models\StopFact;
use App\Models\StopReason;
use App\Models\StopType;
use App\Models\SynchronousType;
use App\Models\TorqueCoefficient;
use App\Models\TorqueMeasuringEquipment;
use App\Models\TransitCompany;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkGroupCenter;
use App\Models\YearMap;
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
        SaleCompany::class => CrudPolicy::class,
        SaleInvoice::class => CrudPolicy::class,
        GeneralProductItem::class => CrudPolicy::class,
        SynchronousType::class => CrudPolicy::class,
        ProductionTest::class => CrudPolicy::class,
        ProductionTestResult::class => CrudPolicy::class,
        ShortageItem::class => CrudPolicy::class,
        IncidentCategory::class => CrudPolicy::class,
        PalletType::class => CrudPolicy::class,
        Pallet::class => CrudPolicy::class,
        ColorMap::class => CrudPolicy::class,
        YearMap::class => CrudPolicy::class,
        BodyOption::class => CrudPolicy::class,
        CabinNumber::class => CrudPolicy::class,
        Setting::class => CrudPolicy::class,
        Screw::class => CrudPolicy::class,
        QcCode::class => CrudPolicy::class,
        TorqueMeasuringEquipment::class => CrudPolicy::class,
        TorqueCoefficient::class => CrudPolicy::class,
        ProductionChecklist::class => CrudPolicy::class,
        ItemInputChecklist::class => CrudPolicy::class,
        CopPlan::class => CrudPolicy::class,
        ItemTechnicalChecklist::class => CrudPolicy::class,
        ProductBom::class => CrudPolicy::class,
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
