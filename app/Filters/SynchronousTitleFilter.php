<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SynchronousTitleFilter
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
        if (!request()->has('synchronous_title') || !request()->get('synchronous_title')) {
            return $next($request);
        }
        return $next($request)->whereHas('synchronousType', function ($query) {
            $query->where('title', request()->get('synchronous_title'));
        });

    }

}
