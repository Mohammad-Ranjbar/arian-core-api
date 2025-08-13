<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CreatedAtToFilter
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
        if (!request()->has('created_at_to') || !request()->get('created_at_to')) {
            return $next($request);
        }
        return $next($request)->where('created_at', '<=', request()->input('created_at_to'));
    }

}
