<?php

namespace App\Filters;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BlockItemMakeCodeIdFilter
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
        if (!request()->has('block_item_make_code_id') || !request()->get('block_item_make_code_id')) {
            return $next($request);
        }
        return $next($request)->whereHas('vehicle', function ($q) {
            $q->where('make_code_id', request()->get('block_item_make_code_id'));
        });
    }

}
