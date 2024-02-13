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


    /**
     * @param  string  $uuid
     */
    public function __construct(public readonly string $uuid)
    {

    }


    /**
     * @return void
     */
    public function handle(): void
    {
        /** @var Job $job */
        $job = Job::query()->find($this->uuid);

//        if ($job->getAttribute('status') !== 'pending') {
//            return;
//        }

        $job->start();

        $service = ImporterServiceFactory::getInstance($job->getAttribute('type'));
        $data = $job->getAttribute('data');
        $file = File::query()->find($data['file']);
        /** @var File $file */
        $service->import($job->getAttribute('uuid'), $file);

        $job->finish();
    }
}
