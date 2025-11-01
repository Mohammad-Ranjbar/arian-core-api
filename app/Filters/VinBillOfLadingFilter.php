<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class VinBillOfLadingFilter
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
        if (!request()->has('vin_bill_of_lading') || !request()->get('vin_bill_of_lading')) {
            return $next($request);
        }

        return $next($request)->whereHas('vehicles', function ($query) {
            $query->where('vin', request()->get('vin_bill_of_lading'));
        });
    }

}
