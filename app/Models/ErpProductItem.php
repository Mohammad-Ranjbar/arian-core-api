<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpProductItem extends Model
{
    use HasFactory;

    const ENGINE='engine';
    /**
     * @var string[]
     */
    protected $fillable = [
        'reference',
        'tracking_code',
        'title',
        'description',
        'type',
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
}
