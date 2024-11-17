<?php

namespace App\Listeners;

use App\Events\ErpJobEvent;
use App\Models\ErpJob;
use App\Services\SendToErpEventService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendErpJobUniqueListener implements ShouldQueue
{

    public $queue = ErpJob::QUEUE_UNIQUE;

    public function __construct()
    {
        //
    }

    public function handle(ErpJobEvent $event): void
    {
        SendToErpEventService::send($event);
    }

}
