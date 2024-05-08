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


    /**
     * @param $id
     * @param $url
     * @param $method
     * @param $params
     */
    public function __construct(private $id, private $url, private $method, private $params)
    {
        //
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $record = Tracking::query()->find($this->id);
        $this->update($record, ErpWsdlClient::run($this->url, $this->method, $this->params));
    }

    /**
     * @param $record
     * @param $result
     * @return void
     */
    private function update($record, $result): void
    {
        $record->update(['data' => $result]);
        $data = Tracking::query()->find($this->id);
        TrackingEvent::dispatch(new TrackingResource($data));
    }

    /**
     * @return void
     */
    public function failed(): void
    {
        $record = Tracking::query()->find($this->id);
        $this->update($record, [
            "status" => "error",
            "message" => "cant received response from ERP."

        ]);
    }
}
