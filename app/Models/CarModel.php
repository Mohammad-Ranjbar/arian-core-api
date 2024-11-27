<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'make_code_id',
        'cabin_serial_chr_1',
        'cabin_serial_chr_2',
        'year_map_id',
    ];

    public function yearMap(): BelongsTo
    {
        return $this->belongsTo(YearMap::class);
    }

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
}
