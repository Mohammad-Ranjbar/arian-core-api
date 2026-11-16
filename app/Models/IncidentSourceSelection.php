<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IncidentSourceSelection extends Model
{
    public const MODEL_TYPES = [
        'work_group_centers' => WorkGroupCenter::class,
        'locations' => Location::class,
    ];

    protected $fillable = ['model_type', 'model_id', 'user_id'];

    protected $casts = ['model_id' => 'integer', 'user_id' => 'integer'];

    public function incidentSource(): BelongsTo
    {
        return $this->belongsTo(IncidentSource::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
