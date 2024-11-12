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

class SendGateToErpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private $id)
    {
        //
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $record = Tracking::query()->find($this->id);


        if ($record->getAttribute('type') == 'exit') {
            $response = $this->exit($record->getAttribute('vin'));
        } elseif ($record->getAttribute('type') == 'enter') {
            $response = $this->enter($record->getAttribute('vin'));
        }

        if ($response['status'] !== 'ok') {
            $this->update($record, [
                "status"  => "error",
                "message" => $response['message']
            ]);
            dispatch(new SendGateToErpJob($this->id));
        }else{
            $this->update($record, [
                "status"  => "ok"
            ]);
        }

    }


    private function exit(string $vin): array
    {
        return ErpWsdlClient::run(config('erp.gate'), 'ApproveExit',
            ['barcodeInfoP' => $vin]);

    }

    private function enter(string $vin): array
    {
        return ErpWsdlClient::run(config('erp.approve'), 'ApproveReturn',
            ['vINP' => $vin]);

    }


    public function failed(): void
    {
        $record = Tracking::query()->find($this->id);
        $this->update($record, [
            "status"  => "error",
            "message" => "cant received response from ERP."
        ]);
//        dispatch(new SendGateToErpJob($this->id));
    }

    private function update($record, $result): void
    {
        $record->update(['data' => $result]);
        $data = Tracking::query()->find($this->id);
        TrackingEvent::dispatch(new TrackingResource($data));
    }
}
