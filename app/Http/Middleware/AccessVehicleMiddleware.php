<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AccessVehicleMiddleware
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user  */
        $user = Auth::user();
        if($user->hasRole('super-admin')){
            return $next($request);
        }
        if($user->can('access', $request->route()->parameter('vehicle'))) return $next($request);
        abort('403', 'access denied');
    }
}
