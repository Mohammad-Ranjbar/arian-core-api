<?php

namespace App\Filters;

use Closure;

class CreatedAtToFilter
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('created_at_to')) {
            return $next($request);
        }
        return $next($request)->where('created_at','<=' , request()->input('created_at_to'));
    }

}