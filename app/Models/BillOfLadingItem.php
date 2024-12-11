<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfLadingItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['type'];
    protected $table = 'bill_of_lading_item';

    public function billOfLading(): BelongsTo
    {
        return $this->belongsTo(BillOfLading::class);
    }
}
