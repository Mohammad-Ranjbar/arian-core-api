<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessUserMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('skip_access')){
            return $next($request);
        }
        $request->merge(['access_user' => true]);
        return $next($request);
    }
}
