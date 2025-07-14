<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class MakeCode extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'reference',
        'title'
    ];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return HasMany
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }


    /**
     * @return HasMany
     */
    public function saleLocations(): HasMany
    {
        return $this->hasMany(SaleLocation::class);
    }

    public function elements(): BelongsToMany
    {
        return $this->belongsToMany(Element::class);
    }

    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class);
    }

    public function productionControllers(): BelongsToMany
    {
        return $this->belongsToMany(ProductionControl::class);
    }
}
