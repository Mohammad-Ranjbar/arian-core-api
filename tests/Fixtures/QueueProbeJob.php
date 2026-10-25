<?php

namespace Tests\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class QueueProbeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public string $marker, public int $seconds = 0) {}

    public function handle(): void
    {
        Redis::set($this->marker.':started', '1');
        if ($this->seconds > 0) {
            sleep($this->seconds);
        }
        Redis::set($this->marker.':finished', '1');
    }
}
