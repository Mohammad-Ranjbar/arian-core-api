<?php

namespace App\Console;

use App\Console\Commands\RetryErpJobCommand;
use App\Console\Commands\SyncInvitationsCommand;
use App\Console\Commands\SyncPDIBlockCommand;
use App\Console\Commands\SyncProductItemsCommand;
use App\Console\Commands\SyncServiceItemsCommand;
use App\Console\Commands\UpdateContractOkCommand;
use App\Console\Commands\UpdateOnlineOrdersCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(SyncProductItemsCommand::class)->daily()->at('00:00')->withoutOverlapping();
        $schedule->command(SyncServiceItemsCommand::class)->daily()->at('00:00')->withoutOverlapping();
        $schedule->command(SyncInvitationsCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(RetryErpJobCommand::class)->everyTenMinutes()->withoutOverlapping();
        $schedule->command(UpdateOnlineOrdersCommand::class)->everyTenMinutes()->withoutOverlapping();
        $schedule->command(UpdateContractOkCommand::class)->everyTenMinutes()->withoutOverlapping();
        $schedule->command(SyncPDIBlockCommand::class)->daily()->at('02:00')->withoutOverlapping();
    }


    /**
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
