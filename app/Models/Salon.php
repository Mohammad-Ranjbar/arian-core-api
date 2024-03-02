<?php

namespace App\Models;

use App\Services\Erp\SendErpTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salon extends Model
{
    use HasFactory, SoftDeletes,SendErpTrait;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title'
    ];

    /**
     * @return HasMany
     */
    public function productionLine(): HasMany
    {
        return $this->hasMany(ProductionLine::class);
    }

}
