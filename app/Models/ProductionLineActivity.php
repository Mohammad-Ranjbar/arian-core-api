<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLineActivity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'production_line_id',
        'station_id',
        'stop_reason_id',
        'stop_type_id',
        'description',
        'note',
        'start_at',
        'stop_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'stop_at'  => 'datetime'
    ];

    /**
     * @return BelongsTo
     */
    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * @return BelongsTo
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * @return BelongsTo
     */
    public function stopReason(): BelongsTo
    {
        return $this->belongsTo(StopReason::class);
    }

    /**
     * @return BelongsTo
     */
    public function stopType(): BelongsTo
    {
        return $this->belongsTo(StopType::class);
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->logs()->latest()->where('action', 'approved')->exists();
    }

    /**
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ProductionLineActivityLogs::class);
    }
}
