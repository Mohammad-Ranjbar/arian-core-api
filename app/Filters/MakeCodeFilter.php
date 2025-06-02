<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class MakeCodeFilter
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
        if ($request->filled('make_code_id')) {
            return $next($request)->where('make_code_id', request()->input('make_code_id'));
        } elseif ($request->filled('make_code')) {
            return $next($request)->whereHas('vehicle', function ($vehicle) use ($request) {
                $vehicle->where('make_code_id', request()->input('make_code'));
            });
        }
        return $next($request);
    }

}
