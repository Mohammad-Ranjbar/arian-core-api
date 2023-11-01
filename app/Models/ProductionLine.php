<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLine extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title', 'salon_id'
    ];

    /**
     * @return BelongsTo
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * @return HasMany
     */
    public function workGroupCenters(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    /**
     * @return HasManyThrough
     */
    public function stations(): HasManyThrough
    {
        return $this->hasManyThrough(Station::class, WorkGroupCenter::class);
    }
}
