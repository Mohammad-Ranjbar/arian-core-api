<?php

namespace App\Jobs;

use App\Models\ErpProductItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(private readonly array $data)
    {

    }


    public function handle(): void
    {
        ErpProductItem::query()->where('reference', $this->data['reference'])
            ->firstOrNew()
            ->fill($this->data)
            ->save();
    }
}
