<?php

namespace App\Console\Commands;

use App\Jobs\ResendProductionTrackingToErpJob;
use App\Models\Tracking;
use Illuminate\Console\Command;

class ResendProductionTrackingToErpCommand extends Command
{

    protected $signature = 'app:resend-production-tracking-to-erp-command';


    public function handle()
    {
        $records = Tracking::query()->whereNull('data')->get();
        if ($records->isEmpty()) {
            $this->info('no records to resend');
            return 0;
        }
        $bar = $this->output->createProgressBar($records->count());
        $bar->start();
        $records->each(function ($record) use($bar){
            dispatch(new ResendProductionTrackingToErpJob($record));
            $bar->advance();
        });
        $bar->finish();
    }
}
