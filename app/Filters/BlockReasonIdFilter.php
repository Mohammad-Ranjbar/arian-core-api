<?php

namespace App\Filters;

use Closure;

class BlockReasonIdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('block_reason_id')) {
            return $next($request);
        }
        return $next($request)->where('block_reason_id', request()->input('block_reason_id'));
    }

}