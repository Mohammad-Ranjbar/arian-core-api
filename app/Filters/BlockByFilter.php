<?php

namespace App\Filters;

use Closure;

class BlockByFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('block_by')) {
            return $next($request);
        }
        return $next($request)->where('block_by', request()->input('block_by'));
    }

}