<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class EngineBarcodeFilter
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
        if (!request()->has('engine_barcode') || !request()->get('engine_barcode')) {
            return $next($request);
        }
        return $next($request)->where('engine_barcode', request()->input('engine_barcode'));
    }

}
