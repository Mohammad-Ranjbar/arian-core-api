<?php

namespace App\Models;

use App\Services\Erp\SendErpTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLine extends Model
{
    use HasFactory, SoftDeletes, SendErpTrait;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title', 'salon_id', 'teams_webhook'
    ];


    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'production_line_user')->withTimestamps();
    }

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
     * @return BelongsToMany
     */
    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }

    /**
     * @return bool
     */
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

    /**
     * @return HasManyThrough
     */
    public function logs(): HasManyThrough
    {
        return $this->hasManyThrough(ProductionLineActivityLogs::class, ProductionLineActivity::class);
    }

    /**
     * @return bool
     */
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

    /**
     * @return Model|HasMany
     */
    public function getLatestActivity(): Model|HasMany
    {
        return $this->activities()->latest()->firstOrFail();
    }

    /**
     * @return HasMany
     */
    public function activities(): HasMany
    {
        return $this->hasMany(ProductionLineActivity::class);
    }

    /**
     * @return MorphMany
     */
    public function tracking(): MorphMany
    {
        return $this->morphMany(Tracking::class, 'model');
    }


}
