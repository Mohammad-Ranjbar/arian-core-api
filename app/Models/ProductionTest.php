<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductionTest extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = ['title'];

    /**
     * @return BelongsToMany
     */
    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class);
    }
}
