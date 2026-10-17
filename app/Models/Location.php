<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{

    /**
     * @var string[]
     */
    protected $fillable = ['title', 'code'];

    /**
     * @return HasMany
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(LocationAttribute::class);
    }

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function palletTransactions(): HasMany
    {
        return $this->hasMany(PalletTransaction::class);
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
