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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(public string $uuid)
    {
        $this->onQueue('exports');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function handle(): void
    {
        /** @var Job $job */
        $job = Job::query()->findOrFail($this->uuid);

        if ($job->getAttribute('status') === 'finished') {
            return;
        }

        $job->start();
        $service = ExporterServiceFactory::getInstance($job);

        $file = $service->export();
        $job->update([
            'result' => [
                'file' => route('dl', ['path' => 'exports/'.$file]),
            ],
        ]);

        $job->finish();
    }

    public function failed(Throwable $exception): void
    {
        $job = Job::query()->find($this->uuid);

        if ($job && $job->getAttribute('status') !== 'finished') {
            $job->update([
                'status' => 'failed',
                'result' => ['message' => 'تهیه خروجی ناموفق بود یا زمان مجاز آن به پایان رسید.'],
            ]);
        }
    }
}
