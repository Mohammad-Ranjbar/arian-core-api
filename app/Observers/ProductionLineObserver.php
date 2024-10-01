<?php

namespace App\Observers;

use App\Models\ProductionLine;
use App\Models\Salon;
use App\Models\Tracking;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProductionLineObserver
{

    /**
     * @param  ProductionLine  $productionLine
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function created(ProductionLine $productionLine): void
    {
        $productionLine->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  ProductionLine  $productionLine
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updated(ProductionLine $productionLine): void
    {
        dd(request()->all());
        $productionLine->sendErp()->delete();
        $productionLine->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  ProductionLine  $productionLine
     * @return void
     */
    public function deleted(ProductionLine $productionLine): void
    {
        $productionLine->sendErp()->delete();
    }
}
