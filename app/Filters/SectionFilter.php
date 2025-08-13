<?php

namespace App\Filters;

use Closure;

class SectionFilter
{


    public function handle($request, Closure $next): mixed
    {
        if (!request()->has('section') || !request()->get('section')) {
            return $next($request);
        }
        if (request()->get('section') == "serial-items") {
            return $next($request)->whereNotNull('type');
        }
        if (request()->get('section') == "general-items") {
            return $next($request)->whereHas('generalProductItem');
        }
        return $next($request);
    }

}
