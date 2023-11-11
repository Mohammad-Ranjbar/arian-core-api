<?php

namespace App\Filters;

use Closure;

class VinFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next): mixed
    {
        if (!request()->has('vin')) {
            return $next($request);
        }
        return $next($request)->where('vin', request()->input('vin'));
    }

}