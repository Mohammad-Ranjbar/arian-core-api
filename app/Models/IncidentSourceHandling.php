<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IncidentSourceHandling extends Model
{
    protected $fillable = ['incident_source_id', 'incident_source_selection_id', 'model_type', 'model_id', 'source_created_at', 'selection_created_at', 'status'];

    protected $casts = ['source_created_at' => 'datetime', 'selection_created_at' => 'datetime'];

    public function selection(): BelongsTo
    {
        return $this->belongsTo(IncidentSourceSelection::class, 'incident_source_selection_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(IncidentSourceAction::class);
    }

    public function action(): HasOne
    {
        return $this->hasOne(IncidentSourceAction::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(IncidentSourceHandlingEvent::class)->orderBy('id');
    }
}
