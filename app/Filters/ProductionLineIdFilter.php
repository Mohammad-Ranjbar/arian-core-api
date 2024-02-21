<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProductionLineIdFilter
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
        if (!request()->has('production_line_id') || !request()->get('production_line_id')) {
            return $next($request);
        }
        return $next($request)->where('production_line_id', request()->input('production_line_id'));
    }

}