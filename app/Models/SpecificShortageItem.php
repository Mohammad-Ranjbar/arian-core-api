<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SpecificShortageItem extends Model
{
    protected $fillable = [
        'qc_code',
        'item_no',
        'description',
        'erp_product_item_id',
        'car_model_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'qc_code' => '84',
    ];

    public function erpProductItem(): BelongsTo
    {
        return $this->belongsTo(ErpProductItem::class);
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }

    public function canDelete(): Attribute
    {
        return Attribute::get(function (){
            if (RegisterShortage::query()->where(['item_no',$this->getAttribute('item_no')])->exists()){
                return false;
            }
            return true;
        });
    }
}
