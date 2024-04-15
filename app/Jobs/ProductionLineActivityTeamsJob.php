<?php

namespace App\Jobs;

use App\Models\ProductionLineActivity;
use App\Services\MicrosoftTeamsNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductionLineActivityTeamsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @param  string  $id
     */
    public function __construct(private readonly string $id)
    {
        //
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $productionLineActivity = ProductionLineActivity::query()->findOrFail($this->id);

        $started = $productionLineActivity->getAttribute('logs')
            ->where('action', 'start')
            ->first();
        $stopped = $productionLineActivity->getAttribute('logs')
            ->where('action', 'stop')
            ->first();


        $stopAt = $productionLineActivity->getAttribute('stop_at') ?? $stopped->created_at;

        $title = "توقف : ";
        $color = "FF0000";
        $summary = jdate($stopAt)->format('Y-m-d H:i:s');
        if ($started) {
            $startAt = $productionLineActivity->getAttribute('start_at') ?? $started->created_at;
            $title = "راه اندازی : ";
            $color = "00FF00";
            $summary = jdate($startAt)->format('Y-m-d H:i:s');
        }
        $summary .= ' '.$title;
        $title .= $productionLineActivity->getAttribute('productionLine')->getAttribute('title');

        if ($started) {
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
            $sections[] = [
                'activityTitle'    => jdate($startAt)->format('Y-m-d H:i:s'),
                'activitySubtitle' => '#'.$productionLineActivity->getAttribute('id'),
                'facts'            => [
                    [
                        'name'  => 'رفع توقف توسط',
                        'value' => $started
                            ->getAttribute('user')
                            ->getAttribute('name')
                    ],
                    [
                        'name'  => 'مدت توقف',
                        'value' => implode(' و ', $arrDiff)
                    ],

                    [
                        'name'  => 'زمان موثر',
                        'value' => $productionLineActivity->getAttribute('effective_time')
                    ],
                ]
            ];
        } else {
            $sections[] = [
                'activityTitle'    => jdate($stopAt)->format('Y-m-d H:i:s'),
                'activitySubtitle' => '#'.$productionLineActivity->getAttribute('id'),
                'facts'            => [
                    [
                        'name'  => 'علت',
                        'value' => $productionLineActivity->getAttribute('stopReason')->getAttribute('title')
                    ],
                    [
                        'name'  => 'عامل',
                        'value' => $productionLineActivity->getAttribute('stopReason')->getAttribute('fact')->getAttribute('title')
                    ],
                    [
                        'name'  => 'نوع توقف',
                        'value' => $productionLineActivity->getAttribute('stopType')->getAttribute('title')
                    ],
                    [
                        'name'  => 'ثبت توقف توسط',
                        'value' => $stopped
                            ->getAttribute('user')
                            ->getAttribute('name')
                    ],
                    [
                        'name'  => 'تیم کاری',
                        'value' => $productionLineActivity->getAttribute('station')->getAttribute('workGroupCenter')->getAttribute('title')
                    ],
                    [
                        'name'  => 'ایستگاه',
                        'value' => $productionLineActivity->getAttribute('station')->getAttribute('title')
                    ],
                    [
                        'name'  => 'توضیحات',
                        'value' => $productionLineActivity->getAttribute('description')
                    ],
                ],
            ];
        }


        MicrosoftTeamsNotificationService::MessageCard(
            $productionLineActivity->getAttribute('productionLine')->getAttribute('teams_webhook'),
            $title,
            $summary,
            $sections,
            $color
        );
    }
}
