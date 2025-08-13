<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UnblockedAtFilter
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
        if (!request()->has('unblocked_at') || !request()->get('unblocked_at')) {
            return $next($request);
        }
        return $next($request)->where('unblocked_at', request()->input('unblocked_at'));
    }

}
