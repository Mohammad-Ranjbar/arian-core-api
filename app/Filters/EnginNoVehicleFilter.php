<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class EnginNoVehicleFilter
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
        if (!request()->has('engin_no_vehicle') || !request()->get('engin_no_vehicle')) {
            return $next($request);
        }
        return $next($request)->where('vehicle', function ($q){
            return $q->where('engin_no', request()->get('engin_no_vehicle'));
        });
    }

}
