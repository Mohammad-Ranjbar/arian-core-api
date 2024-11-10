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
        return $next($request)->withCount([
            'containers', // Count related containers
            'vehicles as painted_body_count' => function ($query) {
                $query->whereIn('data->Item_Type', ['Painted Body', 'Raw Body']);
            },
            'vehicles as vehicle_count' => function ($query) {
                $query->where('data->Item_Type', 'Vehicle');
            },
        ])
            ->when(request()->get('bol_type') === 'containers', function ($query) {
                $query->having('containers_count', '>', 0);
            })
            ->when(request()->get('bol_type') === 'bodies', function ($query) {
                $query->having('painted_body_count', '>', 0);
            })
            ->when(request()->get('bol_type') === 'vehicles', function ($query) {
                $query->having('vehicle_count', '>', 0);
            });
//        return $next($request)->filterByType(request()->input('bol_type'));
    }

}
