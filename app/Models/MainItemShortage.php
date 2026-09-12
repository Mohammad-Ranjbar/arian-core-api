<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MainItemShortage extends Model
{
    //#TODO audit by action log that created user in observer

    protected $fillable = [
        'location_code',
        'car_model_id',
        'car_model_reference',
        'item_no',
        'description',
        'version',
        'is_active',
        'main_item_shortage_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    // records start as drafts; mirrors the column default for version so a
    // freshly created model reports it without a refresh
    protected $attributes = [
        'version' => 1,
        'is_active' => false,
    ];

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function mainItemShortage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'main_item_shortage_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'main_item_shortage_id');
    }

    public function relatedItems(): HasMany
    {
        return $this->hasMany(RelatedItemShortage::class);
    }

    public function actionLog(): BelongsTo
    {
        return $this->morphTo(ActionLog::class);
    }

    /**
     * A draft absorbs related item changes directly; an active record is frozen
     * and forks instead.
     */
    public function isDraft(): bool
    {
        return !$this->is_active;
    }

    /**
     * Fork a new draft carrying version + 1 and a copy of the surviving related
     * items, then deactivate this record so it stays an immutable snapshot.
     *
     * @param array $excludeRelatedItemIds related items left behind by the fork
     */
    public function forkVersion(array $excludeRelatedItemIds = []): self
    {
        $fork = static::query()->create(array_merge(
            $this->only(['location_code', 'car_model_id', 'car_model_reference', 'item_no', 'description']),
            [
                'version'               => $this->version + 1,
                'is_active'             => false,
                'main_item_shortage_id' => $this->id,
            ]
        ));

        $this->relatedItems()
            ->when($excludeRelatedItemIds, fn($q) => $q->whereNotIn('id', $excludeRelatedItemIds))
            ->get()
            ->each(fn(RelatedItemShortage $item) => $fork->relatedItems()->create($item->only(['item_no', 'description'])));

        $this->update(['is_active' => false]);

        return $fork;
    }

}
