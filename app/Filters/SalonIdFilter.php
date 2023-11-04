<?php

namespace App\Filters;

use Closure;

class SalonIdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('salon_id')) {
            return $next($request);
        }
        return $next($request)->where('salon_id', request()->input('salon_id'));
    }

}