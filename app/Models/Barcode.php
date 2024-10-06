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
        if (!empty($data['vin'])) {
            $query = $query->where('vin' , $data['vin']);
        }
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['car_model_id'])) {
            $query = $query->whereHas('vehicle', function ($q) use ($data) {
                $q->whereIn('make_code_id', array($data['car_model_id']));
            });
        }
        if (!empty($data['make_code_id'])) {
            $query = $query->whereHas('vehicle', function ($q) use ($data) {
                $q->whereIn('make_code_id', array($data['make_code_id']));
            });
        }
        if (!empty($data['tracking']) ) {

            if ($data['tracking'] == 0) {
                $query = $query->whereNotIn('code', ['shortage', 'noLabel', 'unreadable'])->whereNull('type');
            }
            if ($data['tracking'] == 1) {
                $query = $query->whereIn('code',['shortage', 'noLabel', 'unreadable'])->whereNotNull('type');
            }
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
