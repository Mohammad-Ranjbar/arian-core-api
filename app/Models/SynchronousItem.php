<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SynchronousItem extends Model
{
    protected $fillable = [
        'synchronous_id',
        'vin',
        'erp_product_item_id',
        'cross_reference','checked'
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class,'vin');
    }

    public function synchronous(): BelongsTo
    {
        return $this->belongsTo(Synchronous::class);
    }

    public function erpProductItem(): BelongsTo
    {
        return $this->belongsTo(ErpProductItem::class);
    }
}
