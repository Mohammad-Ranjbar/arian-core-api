<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProductionTestIdFilter
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
        if (!request()->has('production_test_id') || !request()->get('production_test_id')) {
            return $next($request);
        }
        return $next($request)->where('production_test_id', request()->input('production_test_id'));
    }

}
