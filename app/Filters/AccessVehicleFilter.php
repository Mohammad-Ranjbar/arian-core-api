<?php

namespace App\Filters;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AccessVehicleFilter
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
        /** @var User $user */
        $user = Auth::user();
        return $next($request)->whereHas('vehicle',function ($q) use ($user){
            $q->whereIn('make_code_id',$user->makeCodes()->get()->pluck('id')->all());
        });
    }

}
