<?php

namespace App\Filters;

use Closure;

class IdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('id')) {
            return $next($request);
        }
        return $next($request)->where('id', request()->input('id'));
    }

}