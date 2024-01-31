<?php

namespace App\Filters;

use Closure;

class CreatedAtFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('created_at')) {
            return $next($request);
        }
        return $next($request)->where('created_at', request()->input('created_at'));
    }

}