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
        'erp_product_item_id'
    ];

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
    public function updateErpProductItemId():void
    {
        $record = ErpProductItem::query()->whereNotNull('tracking_code')
            ->where('tracking_code', '!=', '')
            ->whereRaw("'$this->code' LIKE concat ('%',tracking_code,'%')")->first();

        if ($record){
            $this->update(['erp_product_item_id' => $record->getAttribute('id')]);
        }
    }


}
