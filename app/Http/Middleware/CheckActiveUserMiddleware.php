<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveUserMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        if (User::query()->where('id', Auth::id())->first()->getAttribute('is_active') == 0) {
            $request->user()->tokens()->delete();
            Auth::logout();
            abort(Response::HTTP_UNAUTHORIZED,'کاربر غیر فعال میباشد');
        }
        return $next($request);
    }
}
