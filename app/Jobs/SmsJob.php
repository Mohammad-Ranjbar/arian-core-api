<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @param  string  $number
     * @param  string  $content
     */
    public function __construct(private readonly string $number, private readonly string $content)
    {
        //
    }


    /**
     * @return void
     */
    public function handle(): void
    {
        SmsService::jsonSend($this->number, $this->content);
    }
}
