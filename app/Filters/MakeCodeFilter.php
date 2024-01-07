<?php

namespace App\Filters;

use Closure;

class MakeCodeFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('make_code_id')) {
            return $next($request);
        }
        return $next($request)->where('make_code_id', request()->input('make_code_id'));
    }

}