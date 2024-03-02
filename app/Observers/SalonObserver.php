<?php

namespace App\Observers;

use App\Models\Salon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SalonObserver
{
    /**
     * @param  Salon  $salon
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function created(Salon $salon): void
    {
        $salon->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  Salon  $salon
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updated(Salon $salon): void
    {
        $salon->sendErp()->delete();
        $salon->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  Salon  $salon
     * @return void
     */
    public function deleted(Salon $salon): void
    {
        $salon->sendErp()->delete();
    }

}
