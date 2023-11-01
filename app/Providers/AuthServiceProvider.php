<?php

namespace App\Providers;

use App\Models\CarModel;
use App\Models\MakeCode;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Policies\CrudPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

    /**
     * @var string[]
     */
    protected $policies = [
        Role::class           => CrudPolicy::class,
        MakeCode::class       => CrudPolicy::class,
        CarModel::class       => CrudPolicy::class,
        User::class           => CrudPolicy::class,
        Salon::class          => CrudPolicy::class,
        ProductionLine::class => CrudPolicy::class,
    ];


    /**
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
