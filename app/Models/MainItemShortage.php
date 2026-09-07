<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MainItemShortage extends Model
{
    //audit by action log that created user

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

    public function actionLog(): BelongsTo
    {
        return $this->morphTo(ActionLog::class);
    }

}
