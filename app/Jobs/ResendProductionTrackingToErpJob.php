<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResendProductionTrackingToErpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param $record
     */
    public function __construct(private $record)
    {
        //
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $params = [
            'vINL' => $this->record->vin,
            'wCGroup' => '',
            'withTracking' => 'false',
            'withOutput' => 'false',
        ];

        if ($this->record->type == 'exit') {
            $params['withOutput'] = 'true';
        }

        dispatch(new SendTrackingToErpJob($this->record->id, config('erp.post_production'), 'PostProduction_OnlyBIN',
            $params));
    }
}
