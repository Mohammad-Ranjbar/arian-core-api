<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * @var string
     */
    protected $primaryKey = 'vin';
    /**
     * @var string[]
     */
    protected $casts = [
        'vin' => 'string'
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
    ];

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
        return $this->makeCode->users()->where('users.id', $userId)->exists();
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
}
