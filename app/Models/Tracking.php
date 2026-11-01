<?php

namespace App\Models;

use App\Events\TrackingEvent;
use App\Http\Resources\TrackingResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;

class Tracking extends Model
{
    use SoftDeletes;

    protected $table = 'production_tracking';

    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'type',
        'vin',
        'data',
        'is_systemic',
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = ['data' => 'json'];

    public static function getReportData(array $data)
    {

        $query = self::query()->with(['vehicle.carModel', 'vehicle.makeCode', 'user', 'model']);
        if (! empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (! empty($data['vin'])) {
            $query = $query->where('vin', $data['vin']);
        }
        if (! empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (! empty($data['type'])) {
            $query = $query->where('type', $data['type']);
        }
        if (! empty($data['model_type'])) {
            $type = match ($data['model_type']) {
                'salons' => Salon::class,
                'production_lines' => ProductionLine::class,
                'stations' => Station::class,
                'sale_locations' => SaleLocation::class,
            };
            $data['model_type'] = $type;
            $query = $query->where('model_type', $type);
        }
        if (! empty($data['model_id'])) {
            if (is_array($data['model_id'])) {
                $query = $query->whereIn('model_id', $data['model_id']);
            } else {
                $query = $query->where('model_id', $data['model_id']);
            }

        }
        if (! empty($data['user_id'])) {
            $query = $query->where('user_id', $data['user_id']);
        }
        if (! empty($data['sale_location_ids'])) {
            $query = $query->whereHas('model', function ($query) use ($data) {
                $query->whereIn('id', $data['sale_location_ids']);
            });
        }
        if (! empty($data['make_code_id'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data) {
                $query->where('make_code_id', $data['make_code_id']);
            });
        }

        if (! empty($data['paginate'])) {
            return $query->latest()->paginate($data['per_page'] ?? 40);
        }

        return $query->get();
    }

    public static function getReportExitDoorData(array $data): Collection
    {
        $query = self::reportQuery($data)->where('production_tracking.model_type', Location::class);

        // Pick the latest matching timestamp per VIN, then resolve timestamp ties by ID.
        $latestDates = (clone $query)
            ->select('production_tracking.vin')
            ->selectRaw('MAX(production_tracking.created_at) as latest_created_at')
            ->groupBy('production_tracking.vin');

        $latestIds = (clone $query)
            ->joinSub($latestDates, 'latest_tracking', function (JoinClause $join): void {
                $join->on('production_tracking.vin', '=', 'latest_tracking.vin')
                    ->where(function (JoinClause $dates): void {
                        $dates->on('production_tracking.created_at', '=', 'latest_tracking.latest_created_at')
                            ->orWhere(function (JoinClause $dates): void {
                                $dates->whereNull('production_tracking.created_at')
                                    ->whereNull('latest_tracking.latest_created_at');
                            });
                    });
            })
            ->selectRaw('MAX(production_tracking.id)')
            ->groupBy('production_tracking.vin');

        return self::query()
            ->select(['id', 'vin', 'type', 'user_id', 'created_at'])
            ->whereIn('id', $latestIds->toBase())
            ->with([
                'vehicle:vin,car_model_id,make_code_id',
                'vehicle.carModel' => fn ($query) => $query->without('locations')->select('id', 'title', 'reference'),
                'vehicle.makeCode:id,title,reference',
                'user:id,name',
            ])
            ->latest('created_at')
            ->orderByDesc('id')
            ->get();
    }

    private static function reportQuery(array $data): Builder
    {
        $query = self::query();

        if (! empty($data['from'])) {
            $query->where('production_tracking.created_at', '>=', $data['from']);
        }
        if (! empty($data['vin'])) {
            $query->where('production_tracking.vin', $data['vin']);
        }
        if (! empty($data['to'])) {
            $query->where('production_tracking.created_at', '<=', $data['to']);
        }
        if (! empty($data['type'])) {
            $query->where('production_tracking.type', $data['type']);
        }
        if (! empty($data['user_id'])) {
            $query->where('production_tracking.user_id', $data['user_id']);
        }

        if (! empty($data['make_code_id']) || ! empty($data['car_model_id'])) {
            $query->whereHas('vehicle', function (Builder $query) use ($data): void {
                foreach (['make_code_id', 'car_model_id'] as $column) {
                    if (! empty($data[$column])) {
                        $query->where($column, $data[$column]);
                    }
                }
            });
        }

        return $query;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function successJob($response): void
    {
        $this->update(['data' => $response]);
        $this->refresh();
        TrackingEvent::dispatch(new TrackingResource($this));
    }

    public function failedJob($response): void
    {
        $this->update(['data' => $response]);

        TrackingEvent::dispatch(new TrackingResource($this));
    }

    public function temporaryProduction(): HasOneThrough
    {
        return $this->hasOneThrough(
            TemporaryProduction::class,
            Vehicle::class,
            'vin',
            'production_order_number',
            'vin',
            'production_order_number'

        );
    }
}
