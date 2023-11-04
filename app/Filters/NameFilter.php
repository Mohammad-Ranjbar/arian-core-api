<?php

namespace App\Filters;

use Closure;

class NameFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('name')) {
            return $next($request);
        }
        return $next($request)->where('name', 'LIKE', '%'.request()->input('name').'%');
    }

}