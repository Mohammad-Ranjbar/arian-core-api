<?php

namespace App\Filters;

use Closure;

class EmailFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('email')) {
            return $next($request);
        }
        return $next($request)->where('email', 'LIKE', '%'.request()->input('email').'%');
    }

}