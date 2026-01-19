<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestNotification;
use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSMSCommand extends Command
{

    protected $signature = 'test:sms';


    public function handle(): void
    {
        SmsService::jsonSend('09126872183', 'test');
    }
}
