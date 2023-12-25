<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Tracking extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'production_tracking';

    /**
     * @var string[]
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'type',
        'vin',
        'data'
    ];

    /**
     * @var string[]
     */
    protected $casts = ['data' => 'json'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    /**
     * @return MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
