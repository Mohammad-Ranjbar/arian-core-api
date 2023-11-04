<?php

namespace App\Filters;

use Closure;

class CreatedAtToFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('created_at_to')) {
            return $next($request);
        }
        return $next($request)->where('created_at','<=' , request()->input('created_at_to'));
    }

}