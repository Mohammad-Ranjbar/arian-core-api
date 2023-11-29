<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\Job;
use App\Services\Importer\ImporterServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(public string $uuid)
    {

    }


    public function handle(): void
    {
        /** @var Job $job */
        $job = Job::query()->find($this->uuid);

        if($job->status != 'pending') {
            return;
        }

        $job->start();

        $service = ImporterServiceFactory::getInstance($job->type);
        $file = File::query()->find($job->data['file']);
        $service->import($job->id,$file);

        $job->finish();
    }
}
