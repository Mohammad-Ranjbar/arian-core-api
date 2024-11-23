<?php

namespace App\Events;

use App\Models\ErpJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ErpJobEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(public readonly ErpJob $erpJob)
    {

    }



}
