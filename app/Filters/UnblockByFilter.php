<?php

namespace App\Filters;

use Closure;

class UnblockByFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('unblock_by')) {
            return $next($request);
        }
        return $next($request)->where('unblock_by', request()->input('unblock_by'));
    }

}