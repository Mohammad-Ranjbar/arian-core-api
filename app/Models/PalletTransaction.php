<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PalletTransaction extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'pallet_id',
        'location_id',
        'qty',
        'user_id',
        'type',
        'description'
    ];

    public static function getReportData(array $data)
    {
        $query = self::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['type'])) {
            $query = $query->where('type', $data['type']);
        }
        if (!empty($data['pallet_id'])) {
            $query = $query->where('pallet_id', $data['pallet_id']);
        }
        if (!empty($data['location_id'])) {
            $query = $query->where('location_id', $data['location_id']);
        }
        if (!empty($data['qty'])) {
            $query = $query->where('qty', $data['qty']);
        }
        return $query->get();
    }

    public static function getReportStockData(array $data): Collection
    {
        return DB::table('pallet_transactions')
            ->groupBy(['pallet_id', 'location_id'])
            ->leftJoin('locations', 'locations.id', '=', 'pallet_transactions.location_id')
            ->leftJoin('pallets', 'pallets.id', '=', 'pallet_transactions.pallet_id')
            ->leftJoin('pallet_types', 'pallet_types.id', '=', 'pallets.pallet_type_id')
            ->when(!empty($data['location_id']), function ($query) use ($data) {
                return $query->where('locations.id', $data['location_id']);
            })
            ->when(!empty($data['type']), function ($query) use ($data) {
                return $query->where('pallet_transactions.type', $data['type']);
            })
            ->when(!empty($data['from']), function ($query) use ($data) {
                return $query->where('pallet_transactions.created_at','>', $data['from']);
            })
            ->when(!empty($data['to']), function ($query) use ($data) {
                return $query->where('pallet_transactions.created_at','<', $data['to']);
            })
            ->selectRaw(
                'SUM(qty) AS qty ,
                            pallets.title AS pallet_title,
                            locations.title AS location_title ,
                            pallet_types.title AS pallet_type_title'
            )->get();
    }

    /**
     * @return BelongsTo
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo
     */
    public function pallet(): BelongsTo
    {
        return $this->belongsTo(Pallet::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
