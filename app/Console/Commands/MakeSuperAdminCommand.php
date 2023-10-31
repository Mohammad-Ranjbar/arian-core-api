<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdminCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:make-super-admin-command';

    /**
     * @return void
     */
    public function handle():void
    {
        $input['email'] = $this->ask('Email?');
        /** @var User $user */
        $user = User::query()->where($input)->first();
        if(!$user) {
            $input['name'] = $this->ask('What is the name?');
            $input['password'] = bcrypt($this->ask('What is the password?'));
            $user = User::query()->create($input);
        }
        $user->assignRole('super-admin');
    }
}
