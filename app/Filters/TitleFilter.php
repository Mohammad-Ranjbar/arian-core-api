<?php

namespace App\Filters;

use Closure;

class TitleFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('title')) {
            return $next($request);
        }
        return $next($request)->where('title', 'LIKE', '%'.request()->input('title').'%');
    }

}