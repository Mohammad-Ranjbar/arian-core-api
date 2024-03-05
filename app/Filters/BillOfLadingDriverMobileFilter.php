<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BillOfLadingDriverMobileFilter
{

    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle($request, Closure $next): mixed
    {
        if (!request()->has('driver_mobile') || !request()->get('driver_mobile')) {
            return $next($request);
        }

        return $next($request)->whereHas('car', function ($query) {
            $query->whereHas('driver', function ($q) {
                $q->where('mobile', request()->get('driver_mobile'));
            });
        });
    }

}