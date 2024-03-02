<?php

namespace App\Observers;

use App\Models\ProductionLine;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProductionLineObserver
{

    /**
     * @param  ProductionLine  $ProductionLine
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function created(ProductionLine $ProductionLine): void
    {
        $ProductionLine->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  ProductionLine  $ProductionLine
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updated(ProductionLine $ProductionLine): void
    {
        $ProductionLine->sendErp()->delete();
        $ProductionLine->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  ProductionLine  $ProductionLine
     * @return void
     */
    public function deleted(ProductionLine $ProductionLine): void
    {
        $ProductionLine->sendErp()->delete();
    }
}
