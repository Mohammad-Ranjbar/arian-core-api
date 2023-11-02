<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessVehicleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $vehicle = $request->route('vehicle');
        if(auth()->user()->can('access', $vehicle)) {
            return $next($request);
        }
        abort('403', 'access denied');
    }
}
