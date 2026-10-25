<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(private readonly string $number, private readonly string $content)
    {
        $this->onQueue('sms');
    }

    public function handle(): void
    {
        SmsService::jsonSend($this->number, $this->content);
    }
}
