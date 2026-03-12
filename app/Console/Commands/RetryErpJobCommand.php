<?php

namespace App\Console\Commands;

use App\Events\ErpJobEvent;
use App\Models\ErpJob;
use Illuminate\Console\Command;

class RetryErpJobCommand extends Command
{

    protected $signature = 'retry-erp-job';

    protected $description = 'retry erp job where description is "$model = $erpJob->model;"';

    public function handle():void
    {
        $erpJobs = ErpJob::query()
            ->where('description', 'Undefined variable $model')
            ->get();

        $progress = $this->output->createProgressBar($erpJobs->count());
        $progress->setBarCharacter('*');
        $progress->start();

        foreach ($erpJobs as $erpJob) {
            ErpJobEvent::dispatch($erpJob);
            $progress->advance();
        }
        $progress->finish();
        $this->output->writeln('');
        $this->info('all failed erp jobs are retried :)');

    }
}
