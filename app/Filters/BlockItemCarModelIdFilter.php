<?php

namespace App\Filters;

use Closure;

class BlockItemCarModelIdFilter
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next):mixed
    {
        if (!request()->has('block_item_car_model_id')) {
            return $next($request);
        }
        return $next($request)->whereHas('vehicle', function ($q) {
            $q->where('car_model_id', request()->get('block_item_car_model_id'));
        });
    }

}