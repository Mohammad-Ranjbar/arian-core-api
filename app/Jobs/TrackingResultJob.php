<?php

namespace App\Jobs;

use App\Events\TrackingEvent;
use App\Http\Resources\TrackingResource;
use App\Models\Tracking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrackingResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @param  array  $data
     */
    public function __construct(public readonly array $data)
    {
        //
    }


    /**
     * @return void
     */
    public function handle(): void
    {
        Tracking::query()->find($this->data['id'])->update(['data' => $this->data['result']]);
        $data = Tracking::query()->find($this->data['id']);
        TrackingEvent::dispatch(new TrackingResource($data));
    }
}
