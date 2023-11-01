<?php

namespace App\Filters;

use Closure;

class SalonIdFilter
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('salon_id')) {
            return $next($request);
        }
        return $next($request)->where('salon_id', request()->input('salon_id'));
    }

}