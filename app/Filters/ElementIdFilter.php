<?php

namespace App\Filters;

use Closure;

class ElementIdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('element_id')) {
            return $next($request);
        }
        return $next($request)->where('element_id', request()->input('element_id'));
    }

}