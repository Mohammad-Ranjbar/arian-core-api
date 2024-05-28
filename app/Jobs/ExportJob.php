<?php

namespace App\Jobs;

use App\Models\Job;
use App\Services\Exporter\ExporterServiceFactory;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @param string $uuid
     */
    public function __construct(public string $uuid)
    {

    }

    public function maxAttempts(): int
    {
        return 0; // Increase the maximum attempts to 5
    }

    /**
     * @return void
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function handle(): void
    {
        /** @var Job $job */
        $job = Job::query()->find($this->uuid);


//        if ($job->getAttribute('status') !== 'pending') {
//            return;
//        }

        $job->start();
        $service = ExporterServiceFactory::getInstance($job);

        $file = $service->export();
        $job->update([
            'result' => [
                'file' => route('dl', ['path' => 'exports/' . $file]),
            ]
        ]);

        $job->finish();
    }
}
