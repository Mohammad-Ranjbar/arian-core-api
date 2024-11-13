<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class QtyFilter
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
        if (!request()->has('qty') || !request()->get('qty')) {
            return $next($request);
        }
        return $next($request)->where('qty', request()->input('qty'));
    }

}
