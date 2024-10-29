<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleIncident extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'type', 'intensity', 'method', 'description', 'user_id', 'production_tracking_id', 'element_id', 'incident_id',
        'vin', 'checked_at', 'custom_location_type', 'custom_location_id', 'created_at', 'checked_at', 'vin_type', 'incident_category_id'
    ];

    /**
     * @param array $data
     * @return LengthAwarePaginator|Builder[]|Collection
     */
    public static function getReportData(array $data)
    {
        $query = self::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }

        if (!empty($data['paginate'])) {
            return $query->paginate($data['per_page'] ?? 10);
        }
        return $query->get();
    }

    /**
     * @return BelongsTo
     */
    public function incidentCategory(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class);
    }

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
    public function incidentSources(): HasMany
    {
        return $this->hasMany(IncidentSource::class, 'incident_id');
    }

    /**
     * @return MorphTo
     */
    public function customLocation(): MorphTo
    {
        return $this->morphTo();
    }

}
