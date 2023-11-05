<?php

namespace App\Filters;

use Closure;

class IncidentIdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('incident_id')) {
            return $next($request);
        }
        return $next($request)->where('incident_id', request()->input('incident_id'));
    }

}