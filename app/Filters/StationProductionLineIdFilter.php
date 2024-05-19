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
        if (!request()->has('production_line_id') || !request()->get('production_line_id')) {
            return $next($request);
        }
        return $next($request)->whereHas('workGroupCenter',function ($q){
            $q->whereHas('ProductionLine',function ($query){
                $query->where('production_line_id',request()->get('production_line_id'));
            });
        });
    }

}
