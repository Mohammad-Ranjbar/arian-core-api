<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PalletTransaction extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'pallet_id',
        'location_id',
        'qty'
    ];

    /**
     * @return BelongsTo
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo
     */
    public function pallet(): BelongsTo
    {
        return $this->belongsTo(Pallet::class);
    }
}
