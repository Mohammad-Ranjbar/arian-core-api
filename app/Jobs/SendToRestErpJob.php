<?php

namespace App\Jobs;

use App\Services\Erp\RestApi\RestErpJobService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendToRestErpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $id) {}

    public function handle(RestErpJobService $service): void
    {
        $service->execute($this->id);
    }

    public function failed(Throwable $exception): void
    {
        resolve(RestErpJobService::class)->fail($this->id, $exception);
    }
}
