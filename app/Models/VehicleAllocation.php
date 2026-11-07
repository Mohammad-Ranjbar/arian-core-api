<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'vin',
        'data',
        'is_complete',
        'production_order_number',
        'variant_code',
        'car_model_id',
        'make_code_id',
        'due_date',
        'erp_response',
        'user_id',
    ];

    protected $casts = [
        'data' => 'array',
        'is_complete' => 'boolean',
        'due_date' => 'date:Y-m-d',
        'erp_response' => 'array',
    ];

    protected $attributes = [
        'is_complete' => false,
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin', 'vin');
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function makeCode(): BelongsTo
    {
        return $this->belongsTo(MakeCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
