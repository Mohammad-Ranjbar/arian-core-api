<?php

namespace App\Jobs;

use App\Events\TrackingEvent;
use App\Http\Resources\TrackingResource;
use App\Models\Tracking;
use App\Services\Erp\ErpWsdlClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTrackingToErpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(private $id, private $url, private $method, private $params)
    {
        //
    }

    public function handle(): void
    {
        $record = Tracking::query()->find($this->id);
        $this->update($record, ErpWsdlClient::run($this->url, $this->method, $this->params));
    }

    private function update($record, $result): void
    {
        $record->update(['data' => $result]);
        $data = Tracking::query()->find($this->id);
        TrackingEvent::dispatch(new TrackingResource($data));
    }
}
