<?php

namespace App\Filters;

use Closure;

class WorkGroupCenterIdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next): mixed
    {
        if (!request()->has('work_group_center_id')) {
            return $next($request);
        }
        return $next($request)->where('work_group_center_id', request()->input('work_group_center_id'));
    }

}