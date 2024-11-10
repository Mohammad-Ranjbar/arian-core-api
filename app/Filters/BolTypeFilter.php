<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BolTypeFilter
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
        if (!request()->has('Bol_type') || !request()->get('Bol_type')) {
            return $next($request);
        }
        return $next($request)->filterByType(request()->input('Bol_type'));
    }

}
