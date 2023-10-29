<?php

namespace App\Filters;

use Closure;

class CreatedAtFromFilter
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('created_at_from')) {
            return $next($request);
        }
        return $next($request)->where('created_at','>=' , request()->input('created_at_from'));
    }

}