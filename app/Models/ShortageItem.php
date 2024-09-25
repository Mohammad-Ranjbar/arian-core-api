<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortageItem extends Model
{
    protected $fillable = [
        'user_id',
        'vin',
        'erp_product_item_id',
        'status'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    public function erpProductItem(): BelongsTo
    {
        return $this->belongsTo(ErpProductItem::class);
    }
}
