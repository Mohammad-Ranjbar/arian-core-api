<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class VehicleCarModelFilter
{

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle($request, Closure $next): mixed
    {
        if (!request()->has('vehicle_car_model_id') || !request()->get('vehicle_car_model_id')) {
            return $next($request);
        }
        return $next($request)->whereHas('vehicles', function ($q) {
            $q->where('car_model_id', request()->get('vehicle_car_model_id'));
        });

    }

}
