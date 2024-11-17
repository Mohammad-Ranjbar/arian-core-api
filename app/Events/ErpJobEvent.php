<?php

namespace App\Events;

use App\Models\ErpJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ErpJobEvent implements ShouldQueue, ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(public readonly ErpJob $erpJob)
    {
        //
    }


    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->erpJob->getAttribute('section') . 'App.Models.User.' . $this->erpJob->getAttribute('user_id')),
        ];
    }


}
