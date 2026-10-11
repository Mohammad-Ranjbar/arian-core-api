<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MainItemShortage extends Model
{
    protected $fillable = [
        'location_code',
        'car_model_id',
        'car_model_reference',
        'item_no',
        'description',
        'version',
        'is_active',
        'main_item_shortage_id',
        'data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
        'data' => 'array',
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

    public function relatedItems(): HasMany
    {
        return $this->hasMany(RelatedItemShortage::class);
    }

    public function registerShortages(): HasMany
    {
        return $this->hasMany(RegisterShortage::class);
    }

    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }

    public function isDraft(): bool
    {
        return ! $this->is_active;
    }

    public function forkVersion(string $locationCode, array $excludedRelatedItemIds = []): self
    {

        $excludedRelatedItems = $this->relatedItems->whereIn('id', $excludedRelatedItemIds);
        $oldRelateds = $this->relatedItems->whereNotIn('id', $excludedRelatedItemIds);
        $locationCode = $this->location_code !== $locationCode ? $locationCode : $this->location_code;

        $newMain = $this->versions()->create([
            'version' => $this->version + 1,
            'is_active' => false,
            'main_item_shortage_id' => $this->id,
            'location_code' => $locationCode,
            'car_model_id' => $this->car_model_id,
            'car_model_reference' => $this->car_model_reference,
            'item_no' => $this->item_no,
            'description' => $this->description,
            'data' => $this->data,
        ]);

        foreach ($oldRelateds as $oldRelated) {
            RelatedItemShortage::query()->create([
                'main_item_shortage_id' => $newMain->getAttribute('id'),
                'item_no' => $oldRelated->item_no,
                'description' => $oldRelated->description,
                'data' => $oldRelated->data,
            ]);
        }

        foreach ($excludedRelatedItems as $excludedRelatedItem) {
            $excludedRelatedItem->recordRemovalFromFork($newMain);
        }

        return $newMain;
    }

    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'main_item_shortage_id');
    }
}
