<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BolTypeFilter
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
        if (!request()->has('bol_type') || !request()->get('bol_type')) {
            return $next($request);
        }
        return $next($request)->selectRaw("*,
        CASE
            WHEN EXISTS (SELECT 1 FROM containers WHERE containers.model_id = your_models.id) THEN 'containers'
            WHEN EXISTS (SELECT 1 FROM vehicles WHERE vehicles.model_id = your_models.id AND (data->'Item_Type' = 'Painted Body' OR data->'Item_Type' = 'Raw Body')) THEN 'bodies'
            WHEN EXISTS (SELECT 1 FROM vehicles WHERE vehicles.model_id = your_models.id AND data->'Item_Type' = 'Vehicle') THEN 'vehicles'
            ELSE NULL
        END AS type")
            ->having('type', request()->input('bol_type'));
//        return $next($request)->filterByType(request()->input('bol_type'));
    }

}
