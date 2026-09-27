<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelatedItemShortage extends Model
{
    protected $fillable = [
        'main_item_shortage_id',
        'item_no',
        'description',
        'data'
    ];

    public function mainItemShortage(): BelongsTo
    {
        return $this->belongsTo(MainItemShortage::class);
    }
}
