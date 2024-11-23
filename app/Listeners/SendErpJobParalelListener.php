<?php

namespace App\Listeners;

use App\Events\ErpJobEvent;
use App\Models\ErpJob;
use App\Services\SendToErpEventService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendErpJobParalelListener implements ShouldQueue
{

    public $queue = ErpJob::QUEUE_PARALEL;

    public function handle(ErpJobEvent $event): void
    {
      SendToErpEventService::send($event);
    }
}
