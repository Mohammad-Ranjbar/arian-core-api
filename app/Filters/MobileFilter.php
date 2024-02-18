<?php

namespace App\Filters;

use Closure;

class MobileFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('mobile')) {
            return $next($request);
        }
        return $next($request)->where('mobile', request()->input('mobile'));
    }

}