<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\Response;

class VehiclePolicy
{
    /**
     * Create a new policy instance.
     */
    public function access(User $user, Vehicle $vehicle): Response
    {
        return $vehicle->hasAccess($user->getAttribute('id')) ? Response::allow() : Response::deny();
    }
}
