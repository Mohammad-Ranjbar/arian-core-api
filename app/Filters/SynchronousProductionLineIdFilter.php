<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SynchronousProductionLineIdFilter
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
        if (!request()->has('synchronous_production_line_id') || !request()->get('synchronous_production_line_id')) {
            return $next($request);
        }

        return $next($request)->whereHas('synchronousType', function ($query) {
            $query->where('production_line_id', request()->get('synchronous_production_line_id'));
        });
    }

}
