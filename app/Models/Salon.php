<?php

namespace App\Models;

use App\Services\Erp\SendErpTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salon extends Model
{
    use HasFactory, SoftDeletes, SendErpTrait;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title'
    ];

    /**
     * @return HasMany
     */
    public function productionLines(): HasMany
    {
        return $this->hasMany(ProductionLine::class);
    }

    /**
     * @return bool
     */
    public function isEnterUpperLocation(): bool
    {
        return true;
    }

    /**
     * @return Attribute
     */
    protected function upperLocation(): Attribute
    {
        return Attribute::make(
            get: fn() => [
                'is_upper_location' => false,
                'upper_id'          => 0,
                'upper_class'       => null
            ],
        );
    }
}
