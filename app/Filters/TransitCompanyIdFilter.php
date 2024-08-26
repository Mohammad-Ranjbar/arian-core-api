<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TransitCompanyIdFilter
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
        if (!request()->has('transit_company_id') || !request()->get('transit_company_id')) {
            return $next($request);
        }
        return $next($request)->where('transit_company_id', request()->input('transit_company_id'));
    }

}
