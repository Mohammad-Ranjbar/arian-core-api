<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UnblockedAtFromFilter
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
        if (!request()->has('unblocked_at_from') || !request()->get('unblocked_at_from')) {
            return $next($request);
        }
        return $next($request)->where('unblocked_at_from', '>=', request()->input('unblocked_at_from'));
    }

}
