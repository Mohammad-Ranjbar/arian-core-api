<?php

namespace App\Filters;

use Closure;

class CreatedAtFromFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('created_at_from')) {
            return $next($request);
        }
        return $next($request)->where('created_at','>=' , request()->input('created_at_from'));
    }

}