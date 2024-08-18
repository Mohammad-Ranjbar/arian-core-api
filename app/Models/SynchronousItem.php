<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SynchronousItem extends Model
{
    protected $fillable = [
        'synchronous_id',
        'vin'
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function synchronous(): BelongsTo
    {
        return $this->belongsTo(Synchronous::class);
    }
}
