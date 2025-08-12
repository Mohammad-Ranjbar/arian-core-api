<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $with = ['driver'];
    /**
     * @var string[]
     */
    protected $fillable = [
        'driver_id', 'plate', 'car', 'transit_company_id',
    ];

    protected $casts = [
        'plate' => 'json'
    ];

    /**
     * @return BelongsTo
     */
    public function transitCompany(): BelongsTo
    {
        return $this->belongsTo(TransitCompany::class);
    }

    /**
     * @return MorphMany
     */
    public function gatePermits(): MorphMany
    {
        return $this->morphMany(GatePermit::class, 'model');
    }

    /**
     * @return BelongsTo
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function billOfLadings(): HasMany
    {
        return $this->hasMany(BillOfLading::class);
    }

    /**
     * @return Attribute
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->attributes['car'],
        );
    }
}
