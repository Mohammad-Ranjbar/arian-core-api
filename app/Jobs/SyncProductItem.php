<?php

namespace App\Jobs;

use App\Models\Barcode;
use App\Models\ErpProductItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @param  array  $data
     */
    public function __construct(public readonly array $data)
    {

    }


    /**
     * @return void
     */
    public function handle(): void
    {

        $record = ErpProductItem::query()->where('reference', $this->data['reference'])
            ->firstOrCreate($this->data);

        if ($record->wasRecentlyCreated) {
            $this->updateBarcodes($record->tracking_code, $record->id);
        } else {
            if ($record->tracking_code != $this->data['tracking_code']) {
                Barcode::query()->where('erp_product_item_id', $record->id)
                    ->update(['erp_product_item_id' => null]);
                $this->updateBarcodes($record->tracking_code, $record->id);
            }
            $record->update($this->data);
        }
    }

    private function updateBarcodes(string $trackingCode, int $id)
    {
        Barcode::query()
            ->where('code', 'LIKE', '%'.$trackingCode.'%')
            ->update(['erp_product_item_id' => $id]);
    }
}
