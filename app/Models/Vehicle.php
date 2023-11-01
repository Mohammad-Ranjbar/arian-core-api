<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    /**
     * @return BelongsTo
     */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'vin';
    }

    /**
     * @return BelongsTo
     */
    public function makeCode(): BelongsTo
    {
        return $this->belongsTo(MakeCode::class);
    }
}
