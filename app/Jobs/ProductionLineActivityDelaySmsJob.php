<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Services\ContentProductionLineActivitySmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductionLineActivityDelaySmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(private readonly string $id)
    {

    }

    public function handle(): void
    {
        $phones = Setting::query()->where('key', 'notify_with_delay_minute')->first()->users()->get()->pluck('mobile')->all();
        if (empty($phones)) {
            return;
        }
        ContentProductionLineActivitySmsService::sendSms($this->id, $phones);
    }
}
