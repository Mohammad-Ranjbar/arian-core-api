<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SynchronousNumberFilter
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
        if (!request()->has('synchronous_number') || !request()->get('synchronous_number')) {
            return $next($request);
        }
        return $next($request)->whereHas('synchronous',function ($q) {
            $q->whereHas('synchronousType', function ($query) {
                $query->where('title', request()->get('synchronous_number'));
            });
        });
    }

}
