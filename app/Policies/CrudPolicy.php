<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Route;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CrudPolicy
{
    /**
     * @param  User  $user
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function viewAny(User $user): Response
    {
        if (request()->get('options')) {
            return Response::allow();
        }
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * @param  User  $user
     * @return Response
     */
    public function view(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * @param  User  $user
     * @return Response
     */
    public function create(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * @param  User  $user
     * @return Response
     */
    public function update(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * @param  User  $user
     * @return Response
     */
    public function delete(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * @param  User  $user
     * @return Response
     */
    public function restore(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * @param  User  $user
     * @return Response
     */
    public function forceDelete(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }
}
