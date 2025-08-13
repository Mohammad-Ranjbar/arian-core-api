<?php

namespace App\Filters;

use App\Models\Location;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AccessGatePermitFilter
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
        /** @var User $user */
        $locationIds = Location::query()->whereHas('users', function ($user) {
            return $user->where('users.id', Auth::id());
        })->get()->pluck('id')->toArray();

        return $next($request)->where(fn($q) => $q->whereIn('location_id', $locationIds)->orWhereIn('source_id', $locationIds));
    }

}
