<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Synchronous extends Model
{

    protected $table = 'synchronous';
    protected $with = 'synchronousType';
    protected $fillable = [
        'synchronous_type_id',
        'counter',
        'capacity',
        'erp_product_item_id',
        'cross_reference'
    ];

    public function synchronousType(): BelongsTo
    {
        return $this->belongsTo(SynchronousType::class);
    }

    public function SynchronousItems(): HasMany
    {
        return $this->hasMany(SynchronousItem::class);
    }

    public function number(): Attribute
    {
        return new Attribute(
            get: fn() => $this->getRelation('synchronousType')->productionLine->prefix
                . '-' .
                $this->getRelation('synchronousType')->id . '-' .
                str_pad($this->getAttribute('counter'), 5, 0, STR_PAD_LEFT)
        );
    }
}
