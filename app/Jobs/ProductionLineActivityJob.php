<?php

namespace App\Jobs;

use App\Models\ProductionLineActivity;
use App\Services\MicrosoftTeamsNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductionLineActivityJob implements ShouldQueue
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
        $summary = "توقف - #";
        if ($productionLineActivity->getAttribute('start_at')) {
            $summary = "راه اندازی - #";
        }

        $sections = [

            [
                'title'     => $productionLineActivity->getAttribute('productionLine')->getAttribute('title'),
                'sub_title' => $summary.$productionLineActivity->getAttribute('id'),
                'items'     => [
                    [
                        'name'  => 'علت',
                        'value' => $productionLineActivity->getAttribute('stopReason')->getAttribute('title')
                    ],
                    [
                        'name'  => 'عامل',
                        'value' => $productionLineActivity->getAttribute('stopReason')->getAttribute('fact')->getAttribute('title')
                    ],
                    [
                        'name'  => 'وضعیت توقف',
                        'value' => $productionLineActivity->getAttribute('stopType')->getAttribute('title')
                    ],
                    [
                        'name'  => 'تاریخ و ساعت توقف',
                        'value' => jdate($productionLineActivity->getAttribute('stop_at'))
                    ],
                    [
                        'name'  => 'ثبت توقف توسط',
                        'value' => $productionLineActivity->getAttribute('logs')
                            ->where('action', 'stop')
                            ->first()
                            ->getAttribute('user')
                            ->getAttribute('name')
                    ],
                    [
                        'name'  => 'تیم',
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
            ]
        ];

        $summary .= $productionLineActivity->getAttribute('id');
        MicrosoftTeamsNotificationService::send($productionLineActivity->getAttribute('productionLine')->getAttribute('teams_webhook'),
            $summary, $sections);

    }
}
