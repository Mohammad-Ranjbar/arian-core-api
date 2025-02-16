<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTorqueTest extends Model
{
    use HasFactory;

    protected $fillable = ['vin', 'value', 'screw_id', 'user_id'];

    public static function getReportData($data)
    {
        $query = self::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['vin'])) {
            $query = $query->where('vin', $data['vin']);
        }
        if (!empty($data['value'])) {
            $query = $query->where('value', $data['value']);
        }
        if (!empty($data['screw_id'])) {
            $query = $query->where('screw_id', $data['screw_id']);
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
