<?php

namespace App\Jobs;

use App\Services\ContentProductionLineActivitySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductionLineActivitySmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  string  $id
     */
    public function __construct(private readonly string $id)
    {

    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $phones1 = $productionLineActivity->productionLine->users()->get()->pluck('mobile')->all();
        $phones2 = $productionLineActivity->stopReason
            ->fact
            ->users()
            ->get()
            ->pluck('mobile')
            ->all();
        $phones = array_unique(array_merge($phones1, $phones2));

        ContentProductionLineActivitySmsService::sendSms($this->id,$phones);
    }
}
