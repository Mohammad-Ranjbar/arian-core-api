<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class RolesFilter
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
        if (!request()->has('roles')) {
            return $next($request);
        }
        return $next($request)->whereHas('roles', function ($query) {
            $query->whereIn('id', request()->get('roles'));
        });
    }

}