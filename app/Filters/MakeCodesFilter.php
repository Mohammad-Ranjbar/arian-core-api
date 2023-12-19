<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class MakeCodesFilter
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
        if (!request()->has('make_codes')) {
            return $next($request);
        }
        return $next($request)->whereHas('makeCodes', function ($query) {
            $query->whereIn('id', (array)request()->get('make_codes'));
        });
    }

}