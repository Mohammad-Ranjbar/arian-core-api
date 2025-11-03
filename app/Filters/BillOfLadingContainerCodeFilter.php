<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BillOfLadingContainerCodeFilter
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
        if (!request()->has('bol_container_code') || !request()->get('bol_container_code')) {
            return $next($request);
        }

        return $next($request)->whereHas('containers', function ($query) {
          $query->where('code',request()->get('bol_container_code'));
        });
    }

}
