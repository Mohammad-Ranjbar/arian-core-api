<?php

namespace App\Observers;

use App\Models\Station;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class StationObserver
{
    /**
     * @param  Station  $station
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function created(Station $station): void
    {
        $station->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  Station  $station
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updated(Station $station): void
    {
        $station->sendErp()->delete();
        $station->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  Station  $station
     * @return void
     */
    public function deleted(Station $station): void
    {
        $station->sendErp()->delete();
    }


}
