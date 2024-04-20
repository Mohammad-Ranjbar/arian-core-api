<?php

namespace App\Filters;

use App\Models\Vehicle;
use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class QcMakeCodeFilter
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
        if (!request()->has('qc_make_code_id') || !request()->get('qc_make_code_id')) {
            return $next($request);
        }
        return $next($request)->whereHasMorph('model', [Vehicle::class], function ($query) {
            $query->where('make_code_id', request()->get('qc_make_code_id'));
        });
    }

}
