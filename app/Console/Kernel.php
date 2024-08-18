<?php

namespace App\Console;

use App\Console\Commands\CheckCustomerFinanceCommand;
use App\Console\Commands\SyncInvitationsCommand;
use App\Console\Commands\SyncProductItemsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(SyncProductItemsCommand::class)->daily()->at('00:00')->withoutOverlapping();
        $schedule->command(SyncInvitationsCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(CheckCustomerFinanceCommand::class)->everyTenMinutes()->withoutOverlapping();
    }


    /**
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
