<?php

namespace App\Filters;

use Closure;

class IdFilter
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('id')) {
            return $next($request);
        }
        return $next($request)->where('id', request()->input('id'));
    }

}