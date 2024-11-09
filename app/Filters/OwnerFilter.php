<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OwnerFilter
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
        if (!request()->has('owner') || !request()->get('owner')) {
            return $next($request);
        }
        return $next($request)->where('owner', 'LIKE', '%'.request()->input('owner').'%');
    }

}
