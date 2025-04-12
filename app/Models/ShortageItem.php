<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ShortageItem extends Model
{
    protected $fillable = [
        'user_id',
        'vin',
        'erp_product_item_id',
        'status',
        'shortage_items',
        'routing_link_code',
        'shortage_item_id',
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
        if (!empty($data['vin'])) {
            $query = $query->where('vin', $data['vin']);
        }
        if (!empty($data['vins'])) {
            $query = $query->whereIn('vin', array($data['vins']));
        }
        if (!empty($data['items'])) {
            $query = $query->whereHas('erpProductItem', function ($q) use ($data) {
                $q->whereIn('reference', $data['items']);
            });
        }

        if (!empty($data['make_code_id'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data) {
                $query->where('make_code_id', $data['make_code_id']);
            });
        }
        if (!empty($data['car_model_id'])) {
            $query = $query->whereHas('vehicle', function ($query) use ($data) {
                $query->where('car_model_id', $data['car_model_id']);
            });
        }


        return $query->get();
    }

    public function related(): HasMany
    {
        return $this->hasMany(self::class, 'shortage_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin');
    }

    public function erpProductItem(): BelongsTo
    {
        return $this->belongsTo(ErpProductItem::class);
    }

    public function actionLog(): MorphOne
    {
        return $this->morphOne(ActionLog::class, 'model');
    }
}
