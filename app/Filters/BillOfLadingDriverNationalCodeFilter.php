<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BillOfLadingDriverNationalCodeFilter
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
        if (!request()->has('driver_national_code') || !request()->get('driver_national_code')) {
            return $next($request);
        }

        return $next($request)->whereHas('car', function ($query) {
            $query->whereHas('driver', function ($q) {
                $q->where('national_code', request()->get('driver_national_code'));
            });
        });
    }

}