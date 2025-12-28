<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ShipmentNumberFilter
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
        if (!request()->has('shipment_number') || !request()->get('shipment_number')) {
            return $next($request);
        }
        return $next($request)->where('shipment_number', request()->input('shipment_number'));
    }

}
