<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleIncident extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'type', 'intensity', 'method', 'description', 'user_id', 'production_tracking_id', 'element_id', 'incident_id',
        'vin', 'checked_at'
    ];

    /**
     * @return BelongsTo
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * @return BelongsTo
     */
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class);
    }

    /**
     * @return BelongsTo
     */
    public function productionTracking(): BelongsTo
    {
        return $this->belongsTo(Tracking::class, 'production_track_id');
    }

    /**
     * @return HasMany
     */
    public function incidentSources():HasMany
    {
        return $this->hasMany(IncidentSource::class,'incident_id');
    }

}
