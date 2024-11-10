<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TestCarModelIdsFilter
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
        if (!request()->has('test_car_model_ids') || !request()->get('test_car_model_ids')) {
            return $next($request);
        }
        return $next($request)->whereHas('productionTest',function ($q){
            $q->whereHas('carModels',function ($q){
                $q->whereIn('id',(array) request()->get('test_car_model_ids'));
            });
        });
    }

}
