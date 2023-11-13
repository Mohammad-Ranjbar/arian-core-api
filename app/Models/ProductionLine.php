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

    /**
     * @return HasMany
     */
    public function carModels(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }

    public function isStarted(): bool
    {
        $record = $this->logs()
            ->latest()
            ->where('action', '!=', 'approved')
            ->first();
        if (!$record) {
            return true;
        }
        return $record->action == 'start';
    }

    public function logs(): HasManyThrough
    {
        return $this->hasManyThrough(ProductionLineActivityLogs::class, ProductionLineActivity::class);
    }

    public function isStop(): bool
    {
        $record = $this->logs()
            ->latest()
            ->where('action', '!=', 'approved')
            ->first();
        if (!$record) {
            return false;
        }
        return $record->action == 'stop';
    }

    public function getLatestActivity(): Model|HasMany
    {
        return $this->activities()->latest()->firstOrFail();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProductionLineActivity::class);
    }


}
