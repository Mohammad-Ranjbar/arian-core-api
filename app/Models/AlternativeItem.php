<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlternativeItem extends Model
{
    protected $fillable = [
        'alternative_category_id',
        'erp_product_item_id',
        'consumption_coefficient',
        'user_id',
    ];

    public function alternativeCategory(): BelongsTo
    {
        return $this->belongsTo(AlternativeCategory::class);
    }

    public function erpProductItem(): BelongsTo
    {
        return $this->belongsTo(ErpProductItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
