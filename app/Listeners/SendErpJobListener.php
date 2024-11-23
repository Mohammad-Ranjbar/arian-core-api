<?php

namespace App\Listeners;

use App\Events\ErpJobEvent;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendErpJobListener implements ShouldQueue
{

    protected $listeners = [
        'unique' => SendErpJobUniqueListener::class,
        'parallel' => SendErpJobParalelListener::class,
    ];

    /**
     * @throws Exception
     */
    public function handle(ErpJobEvent $event): void
    {
        $listenerClass = $this->listeners[$event->erpJob->getAttribute('queue')] ?? null;

        if ($listenerClass) {
            app($listenerClass)->handle($event);
        } else {
            logger('not found listener');
        }
    }
}
