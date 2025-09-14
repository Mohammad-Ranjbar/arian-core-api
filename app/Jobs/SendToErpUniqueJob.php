<?php

namespace App\Jobs;

use App\Mail\FailedErpJobMail;
use App\Models\ErpJob;
use App\Services\SendToErpEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendToErpUniqueJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $maxExceptions = 2;
    public $backoff = [30, 60, 300, 1800, 3600];

    public function __construct(public readonly string $id)
    {
        //
    }

    public function handle(): void
    {
        SendToErpEventService::send(ErpJob::query()->find($this->id));
    }

    public function failed(Throwable $exception): void
    {
        if ($job = ErpJob::query()->find($this->id)) {
            Mail::to([
                'mo.ranjbar@arian-capital.com',
                'm.fatollahzadeh@auto360.ir',
            ])->send(new FailedErpJobMail($job, $exception));
        }
    }
}
