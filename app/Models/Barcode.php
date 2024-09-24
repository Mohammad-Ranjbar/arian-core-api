<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barcode extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'vin',
        'user_id',
        'code',
        'erp_product_item_id',
        'general_product_item_id',
        'type',
        'created_at'
    ];


    public static function getReportData(array $data)
    {

        $query = self::query()->with('user');
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['make_code'])) {
            $query = $query->whereHas('vehicle', function ($q) use ($data) {
                $q->whereIn('make_code_id', $data['make_code']);
            });
        }

        if (!empty($data['paginate'])) {
            return $query->paginate($data['per_page'] ?? 10);
        }

        return $query;
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    /**
     * @return BelongsTo
     */
    public function erpProductItem(): BelongsTo
    {
        return $this->belongsTo(ErpProductItem::class);
    }

    /**
     * @return void
     */
    public function updateErpProductItemId(): void
    {
        $record = ErpProductItem::query()->whereNotNull('tracking_code')
            ->where('tracking_code', '!=', '')
            ->whereRaw("'$this->code' LIKE concat ('%',tracking_code,'%')")->first();

        if ($record) {
            $this->update(['erp_product_item_id' => $record->getAttribute('id')]);
        }
    }

    /**
     * @return BelongsTo
     */
    public function generalProductItem(): BelongsTo
    {
        return $this->belongsTo(GeneralProductItem::class);
    }

}
