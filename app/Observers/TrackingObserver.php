<?php

namespace App\Observers;

use App\Models\Salon;
use App\Models\Tracking;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class TrackingObserver
{

    /**
     * @param  Tracking  $tracking
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function created(Tracking $tracking): void
    {

        if (
            request()->get('type') === 'enter'
            && !$tracking->getAttribute('model')->isEnterUpperLocation()
            && $tracking->getAttribute('model')->upperLocation['is_upper_location']
        ) {
            Tracking::query()->create([
                'model_type' => $tracking->getAttribute('model')->upperLocation['upper_class'],
                'model_id'   => $tracking->getAttribute('model')->upperLocation['upper_id'],
                'type'       => 'enter',
                'vin'        => request()->route('vehicle')->vin,
                'user_id'    => Auth::id()
            ]);
        }
    }

}
