<?php

namespace App\Filters;

use Closure;

class WorkGroupCenterIdFilter
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('work_group_center_id')) {
            return $next($request);
        }
        return $next($request)->where('work_group_center_id', request()->input('work_group_center_id'));
    }

}