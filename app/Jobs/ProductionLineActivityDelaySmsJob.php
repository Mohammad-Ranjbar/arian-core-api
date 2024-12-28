<?php

namespace App\Jobs;

use App\Models\ProductionLineActivity;
use App\Models\Setting;
use App\Services\SmsService;
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
        $productionLineActivity = ProductionLineActivity::query()->find($this->id);


        $phones = Setting::query()->where('key','notify_with_delay_minute')->first()->users()->get()->pluck('mobile')->all();
        if (empty($phones)) {
            return;
        }
        $started = $productionLineActivity->getAttribute('logs')
            ->where('action', 'start')
            ->first();
        $stopped = $productionLineActivity->getAttribute('logs')
            ->where('action', 'stop')
            ->first();
        $stopAt = $productionLineActivity->getAttribute('stop_at') ?? $stopped->created_at;

        $content[] = '#'.$productionLineActivity->getAttribute('id');
        if ($started) {
            $startAt = $productionLineActivity->getAttribute('start_at') ?? $started->created_at;
            $diff = $stopAt->diff($startAt);

            $arrDiff = [];
            if ($v = $diff->d) {
                $arrDiff[] = $v.' روز';
            }
            if ($v = $diff->h) {
                $arrDiff[] = $v.' ساعت';
            }
            if ($v = $diff->i) {
                $arrDiff[] = $v.' دقیقه';
            }
            if ($v = $diff->s) {
                $arrDiff[] = $v.' ثانیه';
            }
            $content[] = 'رفع توقف:';
            $content[] = $productionLineActivity->getAttribute('productionLine')->title;
            $content[] = 'مدت زمان توقف:';
            $content[] = implode(' و ', $arrDiff);
            $content[] = 'زمان موثر:';
            $content[] = $productionLineActivity->getAttribute('effective_time');
        } else {
            $content[] = 'توقف:';
            $content[] = $productionLineActivity->getAttribute('productionLine')->title;
            $content[] = 'به دلیل:';
            $content[] = $productionLineActivity->getAttribute('stopReason')->title;
            $content[] = 'عامل توقف:';
            $content[] = $productionLineActivity->getAttribute('stopReason')->fact->title;
        }


        foreach ($phones as $phone) {
            SmsService::sendSms($phone, implode("\n", $content));
        }
    }
}
