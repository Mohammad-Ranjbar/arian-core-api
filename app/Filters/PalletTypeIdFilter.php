<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PalletTypeIdFilter
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
        if (!request()->has('pallet_type_id') || !request()->get('pallet_type_id')) {
            return $next($request);
        }
        return $next($request)->where('pallet_type_id', request()->input('pallet_type_id'));
    }

}
