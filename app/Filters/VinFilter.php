<?php

namespace App\Filters;

use App\Http\Requests\GetIncidentRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VinFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('vin')) {
            return $next($request);
        }
        return $next($request)->where('vin', request()->input('vin'));
    }

}