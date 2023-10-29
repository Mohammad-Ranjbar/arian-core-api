<?php

namespace App\Policies;


use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class CrudPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        if(request()->get('options')) {
            return Response::allow();
        }
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): Response
    {
        return $user->can(Route::getCurrentRoute()->getName()) ? Response::allow() : Response::deny();
    }

}
