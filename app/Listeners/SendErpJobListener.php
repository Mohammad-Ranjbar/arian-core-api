<?php

namespace App\Listeners;

use App\Events\ErpJobEvent;
use App\Jobs\SendToErpParallelJob;
use App\Jobs\SendToErpUniqueJob;
use Exception;

class SendErpJobListener
{

    protected $listeners = [
        'unique' => SendToErpUniqueJob::class,
        'parallel' => SendToErpParallelJob::class,
    ];

    /**
     * @throws Exception
     */
    public function handle(ErpJobEvent $event): void
    {
        $jobClass = $this->listeners[$event->erpJob->getAttribute('queue')] ?? null;

        if ($jobClass) {
            dispatch(new $jobClass($event->erpJob->getAttribute('id')))
                ->onQueue($event->erpJob->getAttribute('queue'));
        } else {
            logger('not found listener');
        }
    }
}
