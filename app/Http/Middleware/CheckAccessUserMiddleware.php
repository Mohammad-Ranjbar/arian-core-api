<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessUserMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        $request->merge(['access_user' => true]);
        return $next($request);
    }
}
