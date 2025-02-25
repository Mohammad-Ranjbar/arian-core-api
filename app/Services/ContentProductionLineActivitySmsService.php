<?php

namespace App\Services;

use App\Models\ProductionLineActivity;
use App\Models\Setting;

class ContentProductionLineActivitySmsService
{
    public static function sendSms(string $id,array $phones): void
    {
        $productionLineActivity = ProductionLineActivity::query()->find($id);
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
            $diff2 = $stopAt->diffInMinutes($startAt);
            if ($diff2 > 30) {
                $phones2 = Setting::query()->where('key', 'notify_with_delay_minute')->first()->users()->get()->pluck('mobile')->all();
                $phones = array_unique(array_merge($phones1, $phones2));
            }
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
