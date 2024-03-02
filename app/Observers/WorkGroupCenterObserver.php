<?php

namespace App\Observers;

use App\Models\WorkGroupCenter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class WorkGroupCenterObserver
{

    /**
     * @param  WorkGroupCenter  $WorkGroupCenter
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function created(WorkGroupCenter $WorkGroupCenter): void
    {
        $WorkGroupCenter->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  WorkGroupCenter  $WorkGroupCenter
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updated(WorkGroupCenter $WorkGroupCenter): void
    {
        $WorkGroupCenter->sendErp()->delete();
        $WorkGroupCenter->sendErp()->create([
            'on_enter' => request()->get('on_enter'),
            'on_exit'  => request()->get('on_exit'),
        ]);
    }


    /**
     * @param  WorkGroupCenter  $WorkGroupCenter
     * @return void
     */
    public function deleted(WorkGroupCenter $WorkGroupCenter): void
    {
        $WorkGroupCenter->sendErp()->delete();
    }
}
