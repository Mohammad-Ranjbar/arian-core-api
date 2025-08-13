<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ModelIdFilter
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
        if (!request()->has('model_id') || !request()->get('model_id')) {
            return $next($request);
        }
        return $next($request)->where('model_id', 'LIKE', '%' . request()->input('model_id') . '%');
    }

}
