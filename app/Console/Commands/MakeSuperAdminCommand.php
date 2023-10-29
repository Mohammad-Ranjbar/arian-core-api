<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-super-admin-command';

    /**
     * Execute the console command.
     */
    public function handle()
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
