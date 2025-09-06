<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class EngineNoFilter
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
        if (!request()->has('engin_no') || !request()->get('engin_no')) {
            return $next($request);
        }
        return $next($request)->where('engine_no', request()->get('engin_no'));

    }

}
