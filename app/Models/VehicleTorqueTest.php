<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTorqueTest extends Model
{
    use HasFactory;

    protected $fillable = ['vin', 'value', 'screw_id'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    public function screw(): BelongsTo
    {
        return $this->belongsTo(Screw::class);
    }
}
