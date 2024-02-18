<?php

namespace App\Filters;

use Closure;

class NationalCodeFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('national_code')) {
            return $next($request);
        }
        return $next($request)->where('national_code', request()->input('national_code'));
    }

}