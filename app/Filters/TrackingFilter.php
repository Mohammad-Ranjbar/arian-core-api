<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TrackingFilter
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
        if (!request()->has('tracking') || !request()->get('tracking')) {
            return $next($request);
        }
        if (request()->get('tracking') == 0) {
            return $next($request)->whereNull('erp_product_item_id');
        }
        return $next($request)->whereNotNull('erp_product_item_id');
    }

}
