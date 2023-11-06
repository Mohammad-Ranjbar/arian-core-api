<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarModel extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'reference',
        'production_line_id'
    ];

    /**
     * @return BelongsTo
     */
    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
