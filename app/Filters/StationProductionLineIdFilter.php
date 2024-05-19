<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class StationProductionLineIdFilter
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
        if (!request()->has('station_production_line_id') || !request()->get('station_production_line_id')) {
            return $next($request);
        }
        return $next($request)->whereHas('workGroupCenter',function ($q){
            dd(request()->get('station_production_line_id'));
                $q->where('production_line_id',request()->get('station_production_line_id'));
        });
    }

}
