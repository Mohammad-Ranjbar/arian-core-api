<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed $carModel
 * @property mixed $vin
 * @property mixed $makeCode
 */
class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var bool
     */
    public $incrementing = false;
    /**
     * @var string[]
     */
    protected $with = ['carModel', 'makeCode'];
    /**
     * @var string
     */
    protected $primaryKey = 'vin';
    /**
     * @var string[]
     */
    protected $casts = [
        'vin' => 'string',
        'data' => 'json',
    ];
    /**
     * @var string[]
     */
    protected $fillable = [
        'vin',
        'production_order_number',
        'engine_no',
        'cabin_no',
        'car_model_id',
        'make_code_id',
        'qc_status',
        'grade',
        'grade_description',
        'qc_description',
        'files',
        'current_location',
        'delivered_customer_at',
        'color_code',
        'data',
        'production_type'
    ];


    public function getProductionType(): ?string
    {
        $info = $this->getAttribute('data');

        if (!empty($info['ManufacturingMethod'])) {
            return $info['ManufacturingMethod'];
        }

        return $this->getAttribute('production_type');
    }

    public function shortageItems(): HasMany
    {
        return $this->hasMany(ShortageItem::class, 'vin');
    }

    /**
     * @return BelongsTo
     */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    /**
     * @return BelongsTo
     */
    public function makeCode(): BelongsTo
    {
        return $this->belongsTo(MakeCode::class);
    }

    /**
     * @param $userId
     * @return bool
     */
    public function hasAccess($userId): bool
    {
        return $this->makeCode->users()?->where('users.id', $userId)->exists() ?? false;
    }

    /**
     * @return HasMany
     */
    public function tracking(): HasMany
    {
        return $this->hasMany(Tracking::class, 'vin');
    }

    /**
     * @return HasMany
     */
    public function vehicleIncidents(): HasMany
    {
        return $this->hasMany(VehicleIncident::class);
    }

    /**
     * @return HasMany
     */
    public function blockItems(): HasMany
    {
        return $this->hasMany(BlockItem::class, 'vin');
    }


    /**
     * @return HasMany
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'vin');
    }

    /**
     * @return MorphMany
     */
    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }

    /**
     * @param $locationId
     * @return bool
     */
    public function updateCurrentLocation($locationId): bool
    {
        return $this->update(['current_location' => $locationId]);
    }

    /**
     * @return HasOneThrough
     */
    public function activeBillOfLading(): HasOneThrough
    {
        return $this->hasOneThrough(
            BillOfLading::class,
            BillOfLadingItem::class,
            'model_id',
            'id',
            'vin',
            'bill_of_lading_id'
        )
            ->where('model_type', self::class)
            ->where('bill_of_ladings.status', '!=', 'discharge');
    }

    /**
     * @return BelongsToMany
     */
    public function billOfLadings(): BelongsToMany
    {
        return $this->belongsToMany(BillOfLading::class, 'bill_of_lading_item', 'model_id')
            ->where('model_type', self::class);
    }

    public function serialBarcodes(): Attribute
    {
        $serials = array_map(function ($item) {
            if (isset($item['prefix'])) {
                if (is_array($item['prefix'])) {
                    $item['prefix'] = $item['prefix'][$this->carModel->reference] ?? '';
                }
            }
            return $item;
        }, config('serial_barcodes.' . $this->makeCode->reference));

        return Attribute::get(fn() => $serials);
    }
}
