<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CarModel extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'reference',
    ];


    /**
     * @return BelongsToMany
     */
    public function productionLines(): BelongsToMany
    {
        return $this->belongsToMany(ProductionLine::class);
    }
}
