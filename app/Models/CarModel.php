<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarModel extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'reference',
        'make_code_id',
        'cabin_serial_chr_1',
        'cabin_serial_chr_2',
        'production',
    ];

    public function bodyOptions(): BelongsToMany
    {
        return $this->belongsToMany(BodyOption::class);
    }


    /**
     * @return BelongsToMany
     */
    public function productionLines(): BelongsToMany
    {
        return $this->belongsToMany(ProductionLine::class);
    }

    public function screws(): BelongsToMany
    {
        return $this->belongsToMany(Screw::class);
    }

    public function carModelFiles(): HasMany
    {
        return $this->hasMany(CarModelFile::class);
    }
}
