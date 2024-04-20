<?php

namespace App\Filters;

use App\Models\Vehicle;
use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class QcCarModelFilter
{

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle($request, Closure $next): mixed
    {
        if (!request()->has('qc_car_model_id') || !request()->get('qc_car_model_id')) {
            return $next($request);
        }
        return $next($request)->whereHasMorph('model', [Vehicle::class], function ($query) {
            $query->where('car_model_id', request()->get('qc_car_model_id'));
        });
    }

}
