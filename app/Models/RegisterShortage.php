<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegisterShortage extends Model
{
    use HasFactory;

    public const TYPE_MAIN = 'main';

    public const TYPE_SINGLE = 'single';

    public const STATUS_HAS_SHORTAGE = 'has_shortage';

    public const STATUS_RESOLVED = 'resolve_shortage';

    protected $fillable = [
        'vin',
        'car_model_id',
        'make_code_id',
        'item_no',
        'item_description',
        'type',
        'qty',
        'work_center',
        'status',
        'user_id',
        'register_shortage_id'
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_HAS_SHORTAGE,
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
