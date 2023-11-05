<?php

namespace App\Filters;

use Closure;

class CarModelIdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('car_model_id')) {
            return $next($request);
        }
        return $next($request)->where('car_model_id', request()->input('car_model_id'));
    }

}