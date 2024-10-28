<?php

namespace App\Jobs;

use App\Events\TrackingEvent;
use App\Http\Resources\TrackingResource;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Services\Erp\ErpWsdlClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendUnblockToErpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private $vin)
    {
        //
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $vehicle = Vehicle::query()->where('vin',$this->vin)->first();
        $exists = $vehicle->blockItems()->whereNull('unblocked_at')->exists();
        if (!$exists) {
            $response = ErpWsdlClient::run(config('erp.block_unblock'), 'UnblockingVehicle', ['barcodeInfoP' => $vehicle->vin]);
            if ($response['status'] !== 'ok') {
                dispatch(new SendUnblockToErpJob($this->vin));
            }
        }

    }




    public function failed(): void
    {
        dispatch(new SendUnblockToErpJob($this->vin));
    }

}
