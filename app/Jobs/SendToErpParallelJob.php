<?php

namespace App\Jobs;

use App\Events\ErpJobEvent;
use App\Models\ErpJob;
use App\Services\SendToErpEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendToErpParallelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(public readonly string $id)
    {
        //
    }

    public function handle(): void
    {
        SendToErpEventService::send(ErpJob::query()->find($this->id));
    }
}
