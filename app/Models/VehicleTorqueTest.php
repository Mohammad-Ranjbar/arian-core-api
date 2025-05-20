<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTorqueTest extends Model
{
    use HasFactory;

    protected $fillable = ['vin', 'value', 'screw_id', 'user_id','range_color','range_code'];

    public static function getReportData($data)
    {
        $query = self::query()->with(['vehicle','screw','user']);
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['car_model_id'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data) {
                $query->where('car_model_id', $data['car_model_id']);
            });
        }

        return $query->get();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    public function screw(): BelongsTo
    {
        return $this->belongsTo(Screw::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
