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
        if (!request()->has('bol_type') || !request()->get('bol_type')) {
            return $next($request);
        }
        if (request()->get('bol_type') == 'containers') {
            return $next($request)->whereHas('containers',function ($q){
                $q->wherePivot('bol_type',request()->get('bol_type'));
            });
        }
        return $next($request)->whereHas('vehicles', function ($q) {
            $q->wherePivot('type', request()->get('bol_type'));
        });

    }

}
