<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserAccessToResourceFilter
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
        if (!request()->has('filterAccessUser') || !request()->get('filterAccessUser')) {
            return $next($request);
        }

        return $next($request)->newQuery()->whereHas('users', function ($query) {
            $query->where('user_id', request()->get('filterAccessUser'));
        });
    }

}
