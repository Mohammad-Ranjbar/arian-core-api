<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AllShortageDoneFilter
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
        if (!request()->has('all_shortage_done') || !request()->get('all_shortage_done')) {
            return $next($request);
        }
        return $next($request)->whereDoesntHave('shortageItems', function ($q) {
            $q->where('status', 'pending');
        });
    }

}
