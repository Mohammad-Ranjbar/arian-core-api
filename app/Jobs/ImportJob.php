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
use Throwable;

class ImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $uuid)
    {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        /** @var Job $job */
        $job = Job::query()->findOrFail($this->uuid);

        if ($job->getAttribute('status') === 'finished') {
            return;
        }

        $job->start();

        $service = ImporterServiceFactory::getInstance($job->getAttribute('type'));
        $data = $job->getAttribute('data');

        $file = File::query()->find($data['file']);
        /** @var File $file */
        $service->import($job->getAttribute('uuid'), $file);

        $job->finish();
    }

    public function failed(Throwable $exception): void
    {
        $job = Job::query()->find($this->uuid);

        if ($job && $job->getAttribute('status') !== 'finished') {
            $job->update([
                'status' => 'failed',
                'result' => ['message' => 'ورود اطلاعات ناموفق بود یا زمان مجاز آن به پایان رسید؛ پیش از تکرار، ردیف‌های پردازش‌شده را بررسی کنید.'],
            ]);
        }
    }
}
