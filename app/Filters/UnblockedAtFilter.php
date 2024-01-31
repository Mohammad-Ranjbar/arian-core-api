<?php

namespace App\Filters;

use Closure;

class UnblockedAtFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('unblocked_at')) {
            return $next($request);
        }
        return $next($request)->where('unblocked_at', request()->input('unblocked_at'));
    }

}