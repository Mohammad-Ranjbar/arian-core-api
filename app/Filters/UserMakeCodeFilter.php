<?php

namespace App\Filters;

use App\Models\MakeCode;
use Closure;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserMakeCodeFilter
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
        $list = MakeCode::query()->whereHas('users', function ($query) {
            $query->where('users.id', Auth::id());
        })->get()->pluck('id')->all();

        return $next($request)->whereIn('make_code_id', $list);
    }

}
