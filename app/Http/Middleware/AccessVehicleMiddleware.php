<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        if(auth()->user()->can('access', $request->route()->parameter('vehicle'))) {
            return $next($request);
        }
        abort('403', 'access denied');
    }
}
