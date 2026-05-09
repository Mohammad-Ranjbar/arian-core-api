<?php

namespace App\Models;

use App\Events\TrackingEvent;
use App\Http\Resources\TrackingResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Tracking extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'production_tracking';

    /**
     * @var string[]
     */
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
        $query = self::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['vin'])) {
            $query = $query->where('vin', $data['vin']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['type'])) {
            $query = $query->where('type', $data['type']);
        }
        if (!empty($data['model_type'])) {
            $type = match ($data['model_type']) {
                'salons' => Salon::class,
                'production_lines' => ProductionLine::class,
                'stations' => Station::class,
                'sale_locations' => SaleLocation::class,
            };
            $query = $query->where('model_type', $type);
        }
        if (!empty($data['model_id'])) {
            if (is_array($data['model_id'])) {
                logger('data is array');
                $query = $query->whereIn('model_id', $data['model_id']);
            }
            $query = $query->where('model_id', $data['model_id']);
        }
        if (!empty($data['user_id'])) {
            $query = $query->where('user_id', $data['user_id']);
        }
        if (!empty($data['sale_location_ids'])) {
            $query = $query->whereHas('model', function ($query) use ($data) {
                $query->whereIn('id', $data['sale_location_ids']);
            });
        }
        if (!empty($data['make_code_id'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data) {
                $query->where('make_code_id', $data['make_code_id']);
            });
        }

        if (!empty($data['paginate'])) {
            return $query->latest()->paginate($data['per_page'] ?? 40);
        }
        return $query->get();
    }

    public static function getReportExitDoorData(array $data): Collection|array
    {
        $query = self::query();

        $query = $query->where('model_type', Location::class);
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['vin'])) {
            $query = $query->where('vin', $data['vin']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['type'])) {
            $query = $query->where('type', $data['type']);
        }

        if (!empty($data['user_id'])) {
            $query = $query->where('user_id', $data['user_id']);
        }

        if (!empty($data['make_code_id'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data) {
                $query->where('make_code_id', $data['make_code_id']);
            });
        }

        if (!empty($data['car_model_id'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data) {
                $query->where('car_model_id', $data['car_model_id']);
            });
        }

        return $query->get();
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
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    /**
     * @return MorphTo
     */
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
