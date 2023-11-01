<?php

namespace App\Filters;

use Closure;

class ProductionLineIdFilter
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('production_line_id')) {
            return $next($request);
        }
        return $next($request)->where('production_line_id', request()->input('production_line_id'));
    }

}