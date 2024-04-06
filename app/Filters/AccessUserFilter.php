<?php

namespace App\Filters;

use Closure;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AccessUserFilter
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
        if (!request()->has('access_user') || !request()->get('access_user')) {
            return $next($request);
        }
//        if ($data->isRelation('user')){
//            $data =  $data->newQuery()->where('user_id',Auth::id());
//        }elseif($data->isRelation('users') && $data->getTable() !== 'permissions'){
//            $data = $data->newQuery()->whereHas('users',function ($q){
//                $q->where('user_id',Auth::id());
//            });
//        }
        if ($next($request)->isRelation('user')) {
            return $next($request)->whereHas('users', function ($query) {
                $query->where('user_id', Auth::id());
            });
        }
    }

}