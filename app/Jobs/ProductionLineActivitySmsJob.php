<?php

namespace App\Jobs;

use App\Models\ProductionLineActivity;
use App\Services\SmsService;
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
        $productionLineActivity = ProductionLineActivity::query()->find($this->id);

        $phones1 = $productionLineActivity->productionLine->users()->get()->pluck('mobile')->all();
        $phones2 = $productionLineActivity->stopReason
            ->fact
            ->users()
            ->get()
            ->pluck('mobile')
            ->all();
        $phones =implode(',', array_merge($phones1, $phones2));

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
            $content[] = 'رفع توقف خط';
            $content[] = $productionLineActivity->getAttribute('productionLine')->title;
            $content[] = 'مدت زمان توقف';
            $content[] = implode(' و ', $arrDiff);
            $content[] = 'زمان موثر';
            $content[] = $productionLineActivity->getAttribute('effective_time');
        } else {
            $content[] = 'توقف خط';
            $content[] = $productionLineActivity->getAttribute('productionLine')->title;
            $content[] = 'به دلیل';
            $content[] = $productionLineActivity->getAttribute('stopReason')->fact->title;
            $content[] = 'عامل توقف';
            $content[] = $productionLineActivity->getAttribute('stopReason')->title;
            $content[] = $productionLineActivity->getAttribute('created_at')->title;
        }


        SmsService::sendSms($phones, implode("\n", $content));
    }
}
