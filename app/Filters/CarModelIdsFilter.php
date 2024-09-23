<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CarModelIdsFilter
{

    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle($request, Closure $next): mixed
    {
        if (!request()->has('car_model_ids') || !request()->get('car_model_ids')) {
            return $next($request);
        }
        return $next($request)->whereHas('carModels',function ($q){
            $q->whereIn('id', request()->get('car_model_ids'));
        });
    }

}
