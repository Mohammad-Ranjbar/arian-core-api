<?php

namespace App\Filters;

use Closure;

class BlockItemMakeCodeIdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('block_item_make_code_id')) {
            return $next($request);
        }
        return $next($request)->whereHas('vehicle', function ($q) {
            $q->where('makeCode', request()->block_item_make_code_id);
        });
    }

}