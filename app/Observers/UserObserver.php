<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\UserCreateNotification;

class UserObserver
{

    /**
     * @param  User  $user
     * @return void
     */
    public function created(User $user): void
    {
        $user->notify(new UserCreateNotification($user));
    }


    /**
     * @param  User  $user
     * @return void
     */
    public function updated(User $user): void
    {
        //
    }


    /**
     * @param  User  $user
     * @return void
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * @param  User  $user
     * @return void
     */
    public function restored(User $user): void
    {
        //
    }


}
