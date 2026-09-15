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

    public function relatedItems(): HasMany
    {
        return $this->hasMany(RelatedItemShortage::class);
    }

    public function actionLog(): BelongsTo
    {
        return $this->morphTo(ActionLog::class);
    }

    public function isDraft(): bool
    {
        return !$this->is_active;
    }

    public function forkVersion(string $locationCode): self
    {
        dd($this->location_code);
        $oldRelateds = $this->relatedItems;
        $locationCode = $this->location_code !== $locationCode ? $locationCode : $this->locaiton_code;
        dd($locationCode);
        $newMain = $this->versions()->create([
            'version' => $this->version + 1,
            'is_active' => false,
            'main_item_shortage_id' => $this->id,
            'location_code' => $locationCode,
            'car_model_id' => $this->car_model_id,
            'car_model_reference' => $this->car_model_reference,
            'item_no' => $this->item_no,
            'description' => $this->description,
        ]);

        foreach ($oldRelateds as $oldRelated) {
            RelatedItemShortage::query()->create([
                'main_item_shortage_id' => $newMain->getAttribute('id'),
                'item_no' => $oldRelated->item_no,
                'description' => $oldRelated->description,
            ]);
        }


        return $newMain;
    }

    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'main_item_shortage_id');
    }

}
