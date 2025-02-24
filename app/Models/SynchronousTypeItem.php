<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SynchronousTypeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'synchronous_type_id',
        'car_model_id',
        'erp_product_item_id',
        'cross_reference',
        'is_active'
    ];


    public function synchronousType()
    {
        return $this->belongsTo(SynchronousType::class);
    }

    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }

    public function erpProductItem()
    {
        return $this->belongsTo(ErpProductItem::class);
    }



}
