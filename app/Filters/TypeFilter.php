<?php

namespace App\Filters;

use Closure;

class TypeFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('type')) {
            return $next($request);
        }
        return $next($request)->where('type', request()->input('type'));
    }

}