<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLogCommand extends Command
{
    protected $signature = 'clear:log';

    protected $description = 'clear log file laravel';

    public function handle(): void
    {
        exec('truncate -s 0 '.storage_path('logs/laravel.log'));

        $this->info('clear log file');
    }
}
