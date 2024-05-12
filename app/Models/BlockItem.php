<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockItem extends Model
{
    use HasFactory, SoftDeletes;


    const  array HEADING = [
        'id',
        'blocked_by',
        'unblocked_by',
        'block_reason',
        'unblock_at',
        'description',
        'vin',
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'blocked_by',
        'unblocked_by',
        'block_reason_id',
        'unblocked_at',
        'description',
        'vin',
        'created_at',
    ];

    /**
     * @return BelongsTo
     */
    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * @return BelongsTo
     */
    public function unblockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    /**
     * @return BelongsTo
     */
    public function blockReason(): BelongsTo
    {
        return $this->belongsTo(BlockReason::class, 'block_reason_id');
    }

    /**
     * @return BelongsTo
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

}
