<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpProductItem extends Model
{
    const ENGINE = 'engine';
    /**
     * @var string[]
     */
    protected $fillable = [
        'reference',
        'tracking_code',
        'title',
        'description',
        'type',
        'related_to'
    ];
    /**
     * @var string[]
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    /**
     * @return HasMany
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class);
    }

    /**
     * @return BelongsToMany
     */
    public function productionLineActivities(): BelongsToMany
    {
        return $this->belongsToMany(ProductionLineActivity::class);
    }

    public function related(): HasMany
    {
        return $this->hasMany(self::class,'related_to');
    }
}
