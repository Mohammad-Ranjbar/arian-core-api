<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationAttribute extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['attribute', 'value', 'location_id'];

    protected $casts = ['value' => 'json'];

    /**
     * @return BelongsTo
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
