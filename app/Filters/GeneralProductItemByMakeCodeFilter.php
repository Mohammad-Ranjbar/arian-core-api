<?php

namespace App\Filters;

use Closure;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class GeneralProductItemByMakeCodeFilter
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
        $makeCodeIds = Auth::user()->makeCodes()->get()->pluck('id')->toArray();

        return $next($request)->whereHas('carModels', function ($q) use ($makeCodeIds) {
            $q->whereIn('make_code_id', $makeCodeIds);
        });
    }

}
