<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockItem extends Model
{
    use HasFactory, SoftDeletes;


    const  array HEADING = [
        'id',
        'blocked_by',
        'unblocked_by',
        'block_reason',
        'unblock_at',
        'description',
        'vin',
        'created_at',

    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'blocked_by',
        'unblocked_by',
        'block_reason_id',
        'unblocked_at',
        'description',
        'vin',
        'type',
        'created_at',
    ];

    /**
     * @return BelongsTo
     */
    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * @return BelongsTo
     */
    public function unblockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    /**
     * @return BelongsTo
     */
    public function blockReason(): BelongsTo
    {
        return $this->belongsTo(BlockReason::class, 'block_reason_id');
    }

    /**
     * @return BelongsTo
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }


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
        if (!empty($data['car_model'])) {
            $query = $query->whereHas('vehicle', function ($vehicle) use ($data) {
                return $vehicle->where('car_model_id', $data['car_model']);
            });
        }
        if (!empty($data['make_code'])) {
            $query = $query->whereHas('vehicle', function ($vehicle) use ($data) {
                return $vehicle->where('make_code_id', $data['make_code']);
            });
        }
        return $query->get();
    }

}
